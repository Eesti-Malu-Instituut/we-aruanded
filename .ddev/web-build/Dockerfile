ARG BASE_IMAGE

FROM $BASE_IMAGE

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
RUN sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    -o Dpkg::Options::="--force-confold" \
    --no-install-recommends \
    --no-install-suggests \
    build-essential \
    libpcre2-8-0 \
    libnotify-bin -y