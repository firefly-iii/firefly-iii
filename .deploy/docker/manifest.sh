#!/usr/bin/env bash

echo '{"experimental":true}' | sudo tee /etc/docker/daemon.json
mkdir $HOME/.docker
touch $HOME/.docker/config.json
echo '{"experimental":"enabled"}' | sudo tee $HOME/.docker/config.json
sudo service docker restart

echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

VERSION_TARGET=jc5x/firefly-iii:release-$VERSION

# if the github branch is develop, only push the 'develop' tag
if [ $TRAVIS_BRANCH == "develop" ]; then
    TARGET=jc5x/firefly-iii:develop
    ARM32=jc5x/firefly-iii:develop-arm
    ARM64=jc5x/firefly-iii:develop-arm64
    AMD64=jc5x/firefly-iii:develop-amd64

    echo "GitHub branch is $TRAVIS_BRANCH."
    echo "Push develop-* builds to $TARGET"

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET
fi

# if branch = master AND channel = alpha, push 'alpha'
if [ $TRAVIS_BRANCH == "master" ] && [ $CHANNEL == "alpha" ]; then
    TARGET=jc5x/firefly-iii:alpha
    ARM32=jc5x/firefly-iii:alpha-arm
    ARM64=jc5x/firefly-iii:alpha-arm64
    AMD64=jc5x/firefly-iii:alpha-amd64

    echo "GitHub branch is $TRAVIS_BRANCH."
    echo "Channel is $CHANNEL."
    echo "Push alpha-* builds to $TARGET"

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET

    echo "Push alpha-* builds to $VERSION_TARGET"

    docker manifest create $VERSION_TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $VERSION_TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $VERSION_TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $VERSION_TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $VERSION_TARGET

fi

# if branch is master and channel is alpha, push 'alpha' and 'beta'.
if [ $TRAVIS_BRANCH == "master" ] && [ $CHANNEL == "beta" ]; then
    TARGET=jc5x/firefly-iii:alpha
    ARM32=jc5x/firefly-iii:beta-arm
    ARM64=jc5x/firefly-iii:beta-arm64
    AMD64=jc5x/firefly-iii:beta-amd64

    echo "GitHub branch is $TRAVIS_BRANCH."
    echo "Channel is $CHANNEL."
    echo "Push beta-* builds to $TARGET"

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET

    TARGET=jc5x/firefly-iii:beta
    ARM32=jc5x/firefly-iii:beta-arm
    ARM64=jc5x/firefly-iii:beta-arm64
    AMD64=jc5x/firefly-iii:beta-amd64

    echo "Push beta-* builds to $TARGET"

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET

    echo "Push beta-* builds to $VERSION_TARGET"

    docker manifest create $VERSION_TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $VERSION_TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $VERSION_TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $VERSION_TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $VERSION_TARGET
fi

# if branch is master and channel is stable, push 'alpha' and 'beta' and 'stable'.
if [ $TRAVIS_BRANCH == "master" ] && [ $CHANNEL == "stable" ]; then
    TARGET=jc5x/firefly-iii:alpha
    ARM32=jc5x/firefly-iii:stable-arm
    ARM64=jc5x/firefly-iii:stable-arm64
    AMD64=jc5x/firefly-iii:stable-amd64

    echo "GitHub branch is $TRAVIS_BRANCH."
    echo "Channel is $CHANNEL."
    echo "Push stable-* builds to $TARGET"

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET

    TARGET=jc5x/firefly-iii:beta
    ARM32=jc5x/firefly-iii:stable-arm
    ARM64=jc5x/firefly-iii:stable-arm64
    AMD64=jc5x/firefly-iii:stable-amd64

    echo "Push stable-* builds to $TARGET"

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET

    TARGET=jc5x/firefly-iii:stable
    ARM32=jc5x/firefly-iii:stable-arm
    ARM64=jc5x/firefly-iii:stable-arm64
    AMD64=jc5x/firefly-iii:stable-amd64

    echo "Push stable-* builds to $TARGET"

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET

    TARGET=jc5x/firefly-iii:latest
    ARM32=jc5x/firefly-iii:stable-arm
    ARM64=jc5x/firefly-iii:stable-arm64
    AMD64=jc5x/firefly-iii:stable-amd64

    echo "Push stable-* builds to $TARGET"

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET

    echo "Push stable-* builds to $VERSION_TARGET"

    docker manifest create $VERSION_TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $VERSION_TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $VERSION_TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $VERSION_TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $VERSION_TARGET
fi

echo 'Done!'
# done!