create table callers
(
    id         int(11) unsigned auto_increment
        primary key,
    name       varchar(255) not null,
    number     varchar(20)  not null,
    avatar     text         null,
    created_at timestamp    null,
    updated_at timestamp    null on update current_timestamp()
)
    charset = utf8mb3;

