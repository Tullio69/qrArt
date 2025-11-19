create table content
(
    id           int(11) unsigned auto_increment
        primary key,
    caller_id    int                                                         null,
    caller_name  varchar(255)                                                not null,
    caller_title varchar(255)                                                null,
    content_name varchar(255)                                                null,
    content_type enum ('audio', 'video', 'audio_call', 'video_call', 'html') not null,
    created_at   timestamp                                                   null,
    updated_at   timestamp                                                   null on update current_timestamp()
)
    charset = utf8mb3;

