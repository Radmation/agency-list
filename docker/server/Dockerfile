FROM php:7.4.33-fpm
RUN apt-get update && apt-get install -y \
		libfreetype6-dev \
		libjpeg62-turbo-dev \
		libpng-dev \
		zlib1g-dev \
		libxml2-dev \
		git \
	&& docker-php-ext-install -j$(nproc) iconv \
	&& docker-php-ext-install -j$(nproc) gd \
	&& docker-php-ext-install mysqli \
	&& docker-php-ext-enable mysqli \
	&& docker-php-ext-install pdo pdo_mysql \
	&& docker-php-ext-install soap

# Mail Functionality
RUN curl -Lsf 'https://storage.googleapis.com/golang/go1.8.3.linux-amd64.tar.gz' | tar -C '/usr/local' -xvzf -
ENV PATH /usr/local/go/bin:$PATH
RUN go get github.com/mailhog/mhsendmail
RUN cp /root/go/bin/mhsendmail /usr/bin/mhsendmail