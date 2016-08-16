FROM alpine

MAINTAINER James Zhu <fatindeed@gmail.com>

RUN apk add --no-cache vsftpd \
 && { \
   echo "anon_root=/data"; \
   echo "seccomp_sandbox=NO"; \
 } >> /etc/vsftpd/vsftpd.conf \
 && mkdir /data \
 && rm -rf /var/cache/apk/*

EXPOSE 21

CMD ["vsftpd", "/etc/vsftpd/vsftpd.conf"]