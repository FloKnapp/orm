---
layout: default
title: Relations
permalink: /relations.html
---
## Relations

One of the most important feature of a relational database are relations. A relation is the reference from one row of
a table to another row from the same table or a different table. In our context it means from one object of a subclass
from `Entity` to another object of the same class or another subclass from `Entity`.
 
### Defining Relations

Relations get defined in protected static assoc array `$relations`. Each relation gets a name as key and has three 
settings: cardinality, class and relation. The default cardinality is `one` and you can omit this value. To make it
easier you can write them in an array without or with keys.

Three examples with the same relations:

```php?start_inline=true
class Article extends ORM\ENtity {
  protected static $relations = [
    'user' => [User::class, ['userId' => 'id']],
    'comments' => ['many', ArticleComments::class, ['id' => 'articleId']]
  ];
}
```

```php?start_inline=true
class Article extends ORM\ENtity {
  protected static $relations = [
    'user' => [
        'class' => User::class, 
        'relation' => ['userId' => 'id']
    ],
    'comments' => [
        'cardinality' => 'many',
        'class' => ArticleComments::class,
        'relation' => ['id' => 'articleId']
    ]
  ];
}
```

```php?start_inline=true
class Article extends ORM\ENtity {
  protected static $relations = [
    'user' => [
        self::OPT_RELATION_CLASS => User::class, 
        self::OPT_RELATION_RELATION => ['userId' => 'id']
    ],
    'comments' => [
        self::OPT_RELATION_CARDINALITY => 'many',
        self::OPT_RELATION_CLASS => ArticleComments::class,
        self::OPT_RELATION_RELATION => ['id' => 'articleId']
    ]
  ];
}
```

> We prefer the first one but the third one has auto completion.

### Relation Types

Well known there are three types of relationships between entities ("one to one", "one to many" and "many to many").
In tables a many to many relationship gets solved by two one to many relationships. So there are only two left.

Example (one article has many categories and one category has many articles):

```
+---------+          +-----------------+          +----------+
| Article | 1------n | ArticleCategory | n------1 | Category |
+---------+          +-----------------+          +----------+
```

> If you need additional properties in the relation table you need an entity for it. 

#### One To One

The one to one relationship is very rarely. Mostly it is used to hide additional data from a otherwise big table. It 
is configured for the owner (the table with the foreign key) exactly the same as a one to many relationship.

Example (one article has additional data):

```php?start_inline=true
class Article extends ORM\Entity {
    protected static $relations = [
        'additionalData' => [ArticleAdditionalData::class, ['id' => 'articleId']]
    ];
}

// owner with foreign key: articleId
class ArticleAdditionalData extends ORM\Entity {
    protected static $relations = [
        'article' => [Article::class, ['articleId' => 'id']]
    ];
}

$article = $em->fetch(Article::class, 1);
$additionalData = $article->fetch('additionalData');

echo get_class($additionalData), "\n";                               // ArticleAdditionalData
echo $article === $additionalData->article ? 'true' : 'false', "\n"; // true
```
#### One To Many

This is the most used relationship. You can find it in almost every application. Some Examples are "one customer has 
many orders", "one user wrote many articles", "one developer created many repositories" and so on. The owner should
have a relation with cardinality one and the related entity may have a relation with cardinality many.

Lets see a complete example (one article has many comments):

```php?start_inline=true
class Article extends ORM\Entity {
    protected static $relations = [
        'comments' => ['many', ArticleComments::class, ['id' => 'articleId']]
    ];
}

// owner with foreign key: articleId
class ArticleComments extends ORM\Entity {
    protected static $relations = [
        'article' => [Article::class, ['articleId' => 'id']]
    ];
}

$article = $em->fetch(Article::class, 1);
$comment = $article->fetch('comments')->one();

echo get_class($comment), "\n";                               // ArticleComment
echo $article === $comment->article ? 'true' : 'false', "\n"; // true
```

#### Many To Many

As we saw in the other examples: the owner of the relation is the entity that has the foreign key. In a many to many
relationship (without properties) there exist no owner and both entities have to define the relationship with two
additional options `'table'` and `'opponent'`.
 
It is not very rare and an example might be an article that can have multiple categories and vise versa:

```php
<?php

class Article extends ORM\Entity {
    protected static $relations = [
        'categories' => [Category::class, ['id' => 'articleId'], 'article_category', 'articles']
    ];
}

class Category extends ORM\Entity {
    protected static $relations = [
        'articles' => [Article::class, ['id' => 'categoryId'], 'article_category', 'categories']
    ];
}
```
