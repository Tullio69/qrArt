create table content_related_articles
(
    content_id int(11) unsigned not null,
    article_id int(11) unsigned not null,
    primary key (content_id, article_id),
    constraint content_related_articles_ibfk_1
        foreign key (content_id) references content (id)
            on delete cascade,
    constraint content_related_articles_ibfk_2
        foreign key (article_id) references related_articles (id)
            on delete cascade
)
    charset = utf8mb3;

create index article_id
    on content_related_articles (article_id);

