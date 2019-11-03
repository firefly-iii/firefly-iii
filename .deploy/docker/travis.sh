#!/usr/bin/env bash

echo "travis.sh: I am building channel ${CHANNEL} for version ${VERSION} on architecture ${ARCH}, branch $TRAVIS_BRANCH."

echo '{"experimental":true}' | sudo tee /etc/docker/daemon.json
mkdir $HOME/.docker
touch $HOME/.docker/config.json
echo '{"experimental":"enabled"}' | sudo tee $HOME/.docker/config.json
sudo service docker restart

# First build amd64 image:
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [$ARCH == "arm"]; then
    echo "Because architecture is $ARCH running some extra commands."
    docker run --rm --privileged multiarch/qemu-user-static:register --reset
    
    # get qemu-arm-static binary
    mkdir tmp
    pushd tmp && \
    curl -L -o qemu-arm-static.tar.gz https://github.com/multiarch/qemu-user-static/releases/download/v2.6.0/qemu-arm-static.tar.gz && \
    tar xzf qemu-arm-static.tar.gz && \
    popd
fi

# if the github branch is develop, build and push develop. Don't push a version tag anymore.
if [ $TRAVIS_BRANCH == "develop" ]; then
    LABEL=jc5x/firefly-iii:develop-$ARCH
    echo "GitHub branch is $TRAVIS_BRANCH. Will build and push $LABEL"
    docker build -t $LABEL -f Dockerfile.$ARCH .
    docker push $LABEL
fi

# if branch = master AND channel = alpha, build and push 'alpha'
if [ $TRAVIS_BRANCH == "master" ] && [ $CHANNEL == "alpha" ]; then
    LABEL=jc5x/firefly-iii:alpha-$ARCH
    echo "GitHub branch is $TRAVIS_BRANCH and channel is $CHANNEL. Will build and push $LABEL"
    docker build -t $LABEL -f Dockerfile.$ARCH .
    docker push $LABEL
fi

# if branch is master and channel is alpha, build and push 'alpha' and 'beta'.
if [ $TRAVIS_BRANCH == "master" ] && [ $CHANNEL == "beta" ]; then
    LABEL=jc5x/firefly-iii:beta-$ARCH
    echo "GitHub branch is $TRAVIS_BRANCH and channel is $CHANNEL. Will build and push $LABEL"
    docker build -t $LABEL -f Dockerfile.$ARCH .
    docker push $LABEL

    # then tag as alpha and push:
    docker tag $LABEL jc5x/firefly-iii:alpha-$ARCH
    docker push jc5x/firefly-iii:alpha-$ARCH
    echo "Also tagged $LABEL as jc5x/firefly-iii:alpha-$ARCH and pushed"
fi

# if branch is master and channel is stable, push 'alpha' and 'beta' and 'stable'.
if [ $TRAVIS_BRANCH == "master" ] && [ $CHANNEL == "stable" ]; then
    # first build stable
    LABEL=jc5x/firefly-iii:stable-$ARCH
    echo "GitHub branch is $TRAVIS_BRANCH and channel is $CHANNEL. Will build and push $LABEL"
    docker build -t $LABEL -f Dockerfile.$ARCH .
    docker push $LABEL

    # then tag as beta and push:
    docker tag $LABEL jc5x/firefly-iii:beta-$ARCH
    docker push jc5x/firefly-iii:beta-$ARCH
    echo "Also tagged $LABEL as jc5x/firefly-iii:beta-$ARCH and pushed"

    # then tag as alpha and push:
    docker tag $LABEL jc5x/firefly-iii:alpha-$ARCH
    docker push jc5x/firefly-iii:alpha-$ARCH
    echo "Also tagged $LABEL as jc5x/firefly-iii:alpha-$ARCH and pushed"

    # then tag as latest and push:
    docker tag $LABEL jc5x/firefly-iii:latest-$ARCH
    docker push jc5x/firefly-iii:latest-$ARCH
    echo "Also tagged $LABEL as jc5x/firefly-iii:latest-$ARCH and pushed"
fi

# push to channel 'version' if master + alpha
if [ $TRAVIS_BRANCH == "master" ] && [$CHANNEL == "alpha"]; then
    LABEL=jc5x/firefly-iii:version-$VERSION-$ARCH
    echo "GitHub branch is $TRAVIS_BRANCH and channel is $CHANNEL. Will also push alpha as $LABEL"
    docker tag jc5x/firefly-iii:alpha-$ARCH $LABEL
    docker push $LABEL
fi

# push to channel 'version' if master + beta
if [ $TRAVIS_BRANCH == "master" ] && [$CHANNEL == "beta"]; then
    LABEL=jc5x/firefly-iii:version-$VERSION-$ARCH
    echo "GitHub branch is $TRAVIS_BRANCH and channel is $CHANNEL. Will also push beta as $LABEL"
    docker tag jc5x/firefly-iii:beta-$ARCH $LABEL
    docker push $LABEL
fi

# push to channel 'version' if master + stable
if [ $TRAVIS_BRANCH == "master" ] && [$CHANNEL == "stable"]; then
    LABEL=jc5x/firefly-iii:version-$VERSION-$ARCH
    echo "GitHub branch is $TRAVIS_BRANCH and channel is $CHANNEL. Will also push beta as $LABEL"
    docker tag jc5x/firefly-iii:stable-$ARCH $LABEL
    docker push $LABEL
fi

echo "Done!"