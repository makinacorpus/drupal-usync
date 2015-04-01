# µSync

Minimal yet powerfull config file based features-like toolkit for Drupal 7.

## How does it work

Whatever is the data source (per default a set of YAML files) this module
will create an AST which reprensents the Drupal object tree. This AST is
organized using a strict structure, which can be highlighted by the YAML
file structure. The tree builder objects are responsible from building the
AST from the data source they can understand.

Once the AST created, an object call the visitor will browse the full tree
in a bottom-top fashion (children first) and run a series of processors over
each node, which allows us to modify the tree. For example, the 'inherit'
keyword will be used during this phase to expand nodes using their explicited
parent. Every language specific directives will processed this way.

Once built, the AST can be browsed using another visitor, which will be
driven by a path matching algorithm, allowing us to work on specific path
in the tree.

Real features of this modules are implemented using a specific visitor which
browses the tree using the path matching algorithm, and execute its processors
only on matching nodes in the tree. For example loaders, which are objects
that use typed tree nodes to import Drupal data, will be executed only on
matching paths, which allows us to proceed very easily to partial imports.

## Getting started

### Create a sample module

Let's assume you are working on a custom blog module:

    sites/all/modules/myblog/
        myblog.module
        myblog.info
        myblog.yml

Contest of the myblog.info file:

    name = My blog feature
    description = Very simple blog feature for my site.
    core = 7.x
    usync[] = myblog.yml

Contents of the myblog.yml file:

    field:
      blog_image:
        label: Post photo
        type: image

    entity:
      blog_post:
        blog:
          name: Blog post
          field:
            blog_image: true
            body: true

    view:
      node:
        blog_post:

          default:
            post_image:
              type: image
              settings:
                image_style: thumbnail
            body: true

          teaser:
            blog_image:
              type: image
              settings:
                image_style: thumbnail
            body:
              type: text_summary_or_trimmed
              settings:
                trim_length: 200

### List available data sources on site

    > drush usync-list

    Module  Source
    myblog  myblog.yml 

### List available data in tree

There is three alternative syntaxes for this use case.

Show a tree of everything declared by the myblog module:

    > drush usync-tree --source=myblog:

Show a tree of the myblog.yml file declared by the myblog module:

    > drush usync-tree --source=myblog:myblog.yml

Show a tree of the myblog.yml file accessing it directly:

    > drush usync-tree --source=sites/all/modules/myblog/myblog.yml

Note that the --source parameter will be the same for all drush commands of
this module, which means you can work directly on files without those needing
to be defined by a specific module.

Output for the sample file should be:

     + field.blog_image
     + field.body
     + entity.node.post.field.blog_image
     + entity.node.post.field.body
     + entity.node.post
     + view.node.blog_post.default
     + view.node.post.teaser

### Listing matching elements in tree

Let's use the same source as upper, our 'myblog' module.

    > drush usync-tree --source=myblog: \
        --match=entity.node.%

     + entity.node.post

Matching rules are the following:

 *  Words will match node names strictly

 *  *%* wildcard will match any name

### Injecting configuration into Drupal

Now that you are experienced users of the --source and --match parameters you
can proceed to Drupal injection. Just replace the usync-tree command by
usync-run using the same parameters and it will work.

For example, inject everything which is in the file:

    > drush usync-tree --source=myblog:

Once everything is injected, you can proceed to partial updates, for example
revert all the view modes:

    > drush usync-tree --source=myblog:
        --match=view.node.blog_post.%

## FAQ

### But why?

Why not. Features is slow to revert, features need to be configured via the
UI, and features are not easy to modify manually, features may bring weird
conflicts, and features don't know what to do when there is an error and get
broken. You can't define things easily, you can make objects inherit from
each other one, and features uses CTools, supports Views, and push forward
very bad practices. Drupal Way is unperformant, unperforming, and I needed
something to write things faster and make things run faster.

### But why Yaml?

Why not, and you can use plain old PHP arrays instead if you don't like it.
It will work like a charm.

### Why should I use it?

I'm not your mamma, if you like to click, use features. Choice is yours. Oh
and it's an experimental, unfinshed product, so I would be you, I wouldn't
use it.
