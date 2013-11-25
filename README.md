SlimBlog
=====

This is an example for deploying a [Slim](http://slimframework.com) application on [OpenShift](https://www.openshift.com).
Actually this is almost the same code from [Fabio Di Sotto](https://github.com/fdisotto) who created this nice example of blog coded with the PHP framework **Slim**

This repo is not meant for the blog itself, but as an example of how to give support to an Slim app to be deployed on OpenShift. To see the changes needed, just check the last commits (the ones made by [juanje](https://github.com/juanje)).
You'll see there are just [a few changes](https://github.com/juanje/SlimBlog/compare/fdisotto:765e479...6e9c044) needed.

By the way, the `.openshift/action_hooks/deploy` script is heavily inspired by the same script from the [OpenShift Symfony-2.3.0 cartridge](https://github.com/hasinhayder/openshift-symfony-2.3.0).

Dependencies
---

* Have an OpenShift account and (at least) one spare gear.


Install from the Web
---

You can easily create the blog with the OpenShift web interface by create an **PHP 5.3** application with this URL at the **Source Code** text entry:
`https://github.com/juanje/SlimBlog.git`

Once the application is created you need to add a **Mysql** cartridge and that's all.


Install from the commandline
---

If you feel comfortable with the commandline and you have your `rhc` setup already, you'll probably find much easier to do this:

```
$ rhc app create slimblog php-5.3 mysql-5.1 --from-code https://github.com/juanje/SlimBlog.git
```

Where `slimblog` is the name that you please to choose.


Features
---
* Create new post with live markdown editor
* Edit/Delete posts and manage users
* Manage settings

OpenShift stuff's Author
---
Author:: Juanje Ojeda (<juanje.ojeda@gmail.com>)

Copyright:: 2013, Juanje Ojeda (<juanje.ojeda@gmail.com>)


Upstream's code Author and Copyright
---
**Fabio Di Sotto**
* [Github](https://github.com/fdisotto)
* [Twitter](https://twitter.com/fdisotto)
* [Facebook](https://facebook.com/fdisotto)

