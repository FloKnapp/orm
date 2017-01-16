---
layout: default
title: Relation Definition
permalink: /relationDefinition.html
---
## Relation Definition

One of the most important features of relational databases are references between tables. They are also called
relationships or associations - we just say relation. A relation is the reference from one row of a table to another
row according to foreign key. In our object context it means that an entity references to another entity. In both
contexts it can also be the same table or entity in a parent-child relationship.

We define relations between entities in the static property `$relations`. Each relation gets a name as key and an array
that defines the relationship. It always has to define the related class. The **owner** also needs to define which 
columns refer to the **non-owner**. The **non-owner** does not have to define the relationship, but when it
defines the relationship it needs to define at least the name of the relation in the **owner**.

To make it easier you can write the options in specific order without keys. In this case the order is important - so
you have to stick to the order we show in the following examples.

Here are three examples with the same relations:

```php
<?php //?start_inline=true
class Article extends ORM\ENtity {
  protected static $relations = [
    'user'     => [User::class, ['userId' => 'id']],   // owner
    'comments' => [ArticleComments::class, 'article'], // non-owner
  ];
}
```

```php
<?php //?start_inline=true
class Article extends ORM\ENtity {
  protected static $relations = [
    'user' => [
        'class'     => User::class,
        'reference' => ['userId' => 'id'],
    ],
    'comments' => [
        'class'    => ArticleComments::class,
        'opponent' => 'article',
    ],
  ];
}
```

```php
<?php //?start_inline=true
class Article extends ORM\ENtity {
  protected static $relations = [
    'user' => [
        self::OPT_RELATION_CLASS     => User::class,
        self::OPT_RELATION_REFERENCE => ['userId' => 'id'],
    ],
    'comments' => [
        self::OPT_RELATION_CLASS    => ArticleComments::class,
        self::OPT_RELATION_OPPONENT => 'article',
    ],
  ];
}
```

> We prefer the first one but the third one has auto completion.

| Option                     | Key             | Type     | Description                                      |
|----------------------------|-----------------|----------|--------------------------------------------------|
| `OPT_RELATION_CLASS`       | `'class'`       | `string` | The full qualified name of related class         |
| `OPT_RELATION_REFERENCE`   | `'reference'`   | `array`  | The column definition (column or property name)  |
| `OPT_RELATION_CARDINALITY` | `'cardinality'` | `string` | How many related objects (one or many) can exist |
| `OPT_RELATION_OPPONENT`    | `'opponent'`    | `string` | The name of the relation in related class        |
| `OPT_RELATION_TABLE`       | `'table'`       | `string` | The table name for many to many relations        |

### Relation Types

Well known there are three types of relationships between entities: *one-to-one*, *one-to-many* and *many-to-many*.
Here we want to describe what is required to define them and how you can define the **non-owner**.

One important thing is that we need to know who is the owner. We define the **owner** with `'reference'` and the 
**non-owner** with `'opponent'` - do not define an **owner** with `'opponent'` otherwise you will get unexpected
behaviour.

The cardinality is mostly determined automatically and also overwritten. In the short form, that we use in the examples,
you have to omit the cardinality and there is only one circumstance where a `'one'` is allowed to define.

#### One-To-Many

This is the most used relationship. You can find it in almost every application. Some Examples are "one customer has 
many orders", "one user wrote many articles", "one developer created many repositories" and so on.

To define the **owner** the **required** attributes are `'class'` and `'reference'`. To define the **non-owner** the
**required** attributes are `'class'` and `'opponent'`.

Lets see an example (one article has many comments):

```php?start_inline=true
class Article extends ORM\Entity {
    protected static $relations = [
        'comments' => [ArticleComments::class, 'article']
    ];
}

// owner with foreign key: articleId
class ArticleComments extends ORM\Entity {
    protected static $relations = [
        'article' => [Article::class, ['articleId' => 'id']]
    ];
}

$article = $em->fetch(Article::class, 1);
$comment = $article->fetch($em, 'comments')->one();

echo get_class($comment), "\n";                               // ArticleComment
echo $article === $comment->article ? 'true' : 'false', "\n"; // true
```

#### One-To-One

A *one-to-one* relationship is mostly used to store data, that is not required for every operation, in a separated
table. It is configured exactly the same as a *one-to-many* relationship except for the **non-owner** where the
cardinality can not be determined automatically.

To define the **owner** the **required** attributes are `'class'` and `'reference'`. To define the **non-owner** the
**required** attributes are `'cardinality'`, `'class'` and `'opponent'`.

Example (one article has additional data):

```php?start_inline=true
class Article extends ORM\Entity {
    protected static $relations = [
        'additionalData' => ['one', ArticleAdditionalData::class, 'article']
    ];
}

// owner with foreign key: articleId
class ArticleAdditionalData extends ORM\Entity {
    protected static $relations = [
        'article' => [Article::class, ['articleId' => 'id']]
    ];
}

$article = $em->fetch(Article::class, 1);
$additionalData = $article->fetch($em, 'additionalData');

echo get_class($additionalData), "\n";                               // ArticleAdditionalData
echo $article === $additionalData->article ? 'true' : 'false', "\n"; // true
```

> When you omit the cardinality it is a *one-to-many* relationship and you will get an `EntityFetcher` from fetch.

#### Many-To-Many

A *many-to-many* relationship gets solved by two *one-to-many* relationships in an association table. For our example
we use the relationship between articles and categories. One article has many categories and one category has many
articles. You create another table `ArticleCategory` to solve the relationship:

 * one article has many *article-categories* 
 * one *article-category* has one article and one category
 * one category has many *article-categories*

```
+---------+          +-----------------+          +----------+
| Article | 1------n | ArticleCategory | n------1 | Category |
+---------+          +-----------------+          +----------+
| id      |          | articleId       |          | id       |
| title   |          | categoryId      |          | name     |
+---------+          +-----------------+          +----------+
```

> If you need additional properties in the association table you need an entity for it and create indeed two
> *one-to-many* relationships.

As we have seen in the other examples: the owner of the relation is the entity that has the foreign key. In a
*many-to-many* relationship there is no owner and both entities have to define the relationship. To define the
relationship both entities **require** the attributes `'class'`, `'reference'`, `'opponent'` and `'table'`.

**ATTENTION**: Because we don't have an entity in the middle the foreign key column in the association table has to be
the column name and not the variable name.

Here comes again an example:

```php?start_inline=true
class Article extends ORM\Entity {
    protected static $relations = [
        'categories' => [Category::class, ['id' => 'article_id'], 'articles', 'article_category']
    ];
}

class Category extends ORM\Entity {
    protected static $relations = [
        'articles' => [Article::class, ['id' => 'category_id'], 'categories', 'article_category']
    ];
}

$article = $em->fetch(Article::class, 1);

$category = $article->fetch($em, 'categories')->one();
echo get_class($category), "\n"; // Category

$articlesInCategory = $category->fetch($em, 'articles')->all();
```
