FROM alpine

RUN apk add --no-cache vsftpd \
 && { \
   echo "local_enable=YES"; \
   echo "seccomp_sandbox=NO"; \
 } >> /etc/vsftpd/vsftpd.conf \
 && echo "root:123456" | chpasswd \
 && rm -rf /var/cache/apk/*

EXPOSE 20 21

CMD ["vsftpd", "/etc/vsftpd/vsftpd.conf"]