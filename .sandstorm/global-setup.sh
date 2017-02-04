#!/bin/bash
set -euo pipefail

# Set options for curl. Since we only want to show errors from these curl commands, we also use
# 'cat' to buffer the output; for more information:
# https://github.com/sandstorm-io/vagrant-spk/issues/158

CURL_OPTS="--silent --show-error"
echo localhost > /etc/hostname
hostname localhost

# The following line copies stderr through stderr to cat without accidentally leaving it in the
# output file. Be careful when changing. See: https://github.com/sandstorm-io/vagrant-spk/pull/159
curl $CURL_OPTS https://install.sandstorm.io/ 2>&1 > /host-dot-sandstorm/caches/install.sh | cat

SANDSTORM_CURRENT_VERSION=$(curl $CURL_OPTS -f "https://install.sandstorm.io/dev?from=0&type=install")
SANDSTORM_PACKAGE="sandstorm-$SANDSTORM_CURRENT_VERSION.tar.xz"
if [[ ! -f /host-dot-sandstorm/caches/$SANDSTORM_PACKAGE ]] ; then
    echo -n "Downloading Sandstorm version ${SANDSTORM_CURRENT_VERSION}..."
    curl $CURL_OPTS --output "/host-dot-sandstorm/caches/$SANDSTORM_PACKAGE.partial" "https://dl.sandstorm.io/$SANDSTORM_PACKAGE" 2>&1 | cat
    mv "/host-dot-sandstorm/caches/$SANDSTORM_PACKAGE.partial" "/host-dot-sandstorm/caches/$SANDSTORM_PACKAGE"
    echo "...done."
fi
if [ ! -e /opt/sandstorm/latest/sandstorm ] ; then
    echo -n "Installing Sandstorm version ${SANDSTORM_CURRENT_VERSION}..."
    bash /host-dot-sandstorm/caches/install.sh -d -e "/host-dot-sandstorm/caches/$SANDSTORM_PACKAGE" >/dev/null
    echo "...done."
fi
modprobe ip_tables
# Make the vagrant user part of the sandstorm group so that commands like
# `spk dev` work.
usermod -a -G 'sandstorm' 'vagrant'
# Bind to all addresses, so the vagrant port-forward works.
sudo sed --in-place='' \
        --expression='s/^BIND_IP=.*/BIND_IP=0.0.0.0/' \
        /opt/sandstorm/sandstorm.conf
sudo service sandstorm restart
# Enable apt-cacher-ng proxy to make things faster if one appears to be running on the gateway IP
GATEWAY_IP=$(ip route  | grep ^default  | cut -d ' ' -f 3)
if nc -z "$GATEWAY_IP" 3142 ; then
    echo "Acquire::http::Proxy \"http://$GATEWAY_IP:3142\";" > /etc/apt/apt.conf.d/80httpproxy
fi
# Configure apt to retry fetching things that fail to download.
echo "APT::Acquire::Retries \"10\";" > /etc/apt/apt.conf.d/80sandstorm-retry
