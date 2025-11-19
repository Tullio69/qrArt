create table short_urls
(
    id         int unsigned auto_increment
        primary key,
    content_id int unsigned not null,
    short_code varchar(10)  not null,
    created_at datetime     null,
    updated_at datetime     null,
    constraint short_code_unique
        unique (short_code),
    constraint fk_short_urls_content_id
        foreign key (content_id) references content (id)
            on update cascade on delete cascade
)
    collate = utf8mb4_unicode_ci;

