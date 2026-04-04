FROM postgres:18
ADD https://github.com/timescale/pg_textsearch/releases/download/v1.0.0/pg-textsearch-v1.0.0-pg18-amd64.zip /tmp/
RUN apt-get update && \
    apt-get install -y unzip && \
    unzip /tmp/pg-textsearch-v1.0.0-pg18-amd64.zip -d /tmp/ && \
    dpkg -i /tmp/pg-textsearch-postgresql-18_1.0.0-1_amd64.deb

CMD ["postgres", "-c", "shared_preload_libraries=pg_textsearch"]
