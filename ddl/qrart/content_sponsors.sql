create table content_sponsors
(
    content_id int(11) unsigned not null,
    sponsor_id int(11) unsigned not null,
    primary key (content_id, sponsor_id),
    constraint content_sponsors_ibfk_1
        foreign key (content_id) references content (id)
            on delete cascade,
    constraint content_sponsors_ibfk_2
        foreign key (sponsor_id) references sponsors (id)
            on delete cascade
)
    charset = utf8mb3;

create index sponsor_id
    on content_sponsors (sponsor_id);

