# SilverStripe Permamail
======================
This module adds several enhancements to the core `Email` class in SilverStripe:
* Persisting sent emails in the database
* Allowing "resend" of emails
* Providing user-defined HTML templates for emails
* Send test emails, populated with your specific variables
* Send emails to a `DataList` of Members

## Installation
`composer require unclecheese/permamail:dev-master`

## Requirements
* SilverStripe 3.1.*
* unclecheese/reflection-templates
* unclecheese/gridfield-betterbuttons:1.2.*

## Usage
By default, Permamail is injected as the class for `Email`, meaning `Email::create()` will instantiate a `Permamail` object instead of `Email`. Because `Permamail` is a subclass of `Email`, the API is exactly the same.

If you would like to use Permamail on a case-by-case basis, simply use `new Permamail()` and `new Email()` where you see fit, or override the injector setting in the configuration so that `Email` is the class used for `Email`.

### Example
```php
$e = Email::create()
 	->setFrom('me@example.com')
 	->toMembers(Member::get())
 	->setSubject('Test email')
 	->setUserTemplate('my-template')
 	->populateTemplate(array(
 		'Member' => Member::get()->byID(2)
 	))
 	->send();
```

The API is exactly the same, with only two new methods:
* `toMembers()` accepts an array or `SS_List` of `Member` objects and uses their `Email` properties, sending an email to each one.
* `setUserTemplate()` accepts a string as the identifier of a user-defined template in the CMS. More information in the "Administration" section below.


## The admin interface
Permamail provides a new `ModelAdmin` interface called `Email`, which provides two tabs:

### Outbound Emails 
Shows a list of all emails sent. Offers an option to resend.

![screenshot](http://image.yogile.com/f2kl9eur/ixplmhzeflfceuzm6m39eh-large.png)

![screenshot](http://image.yogile.com/f2kl9eur/ixplmhxse2ymk3t24uq3hp-large.png)

### Email templates
Allows creation of user-defined templates, with default subjects and "From" addresses.

![screenshot](http://image.yogile.com/f2kl9eur/ixplmh7iqa7zlvo9pryd4a-large.png)

### Sending test emails

![screenshot](http://image.yogile.com/f2kl9eur/ixplmhlowmyogsfme63n1q-large.png)

Every user-defined template can send a test email, and populate the template with the variables you have specified. To do this, the template uses the `ReflectionTemplate` class to gather all the variables and blocks in the template, and offers you the opportunity to customise what values are assigned to those variables. Options include:
* A static value (e.g. hardcoded)
* A random object from the database of a given ClassName
* A specific object from the database of a given ClassName

![screenshot](http://image.yogile.com/f2kl9eur/ixplmhankqylmal8griu5w-large.png)

#### Querying specific records to inject into your template
To run a custom query, you can use a query string like an array is used in the `filter()` method of the ORM, for example: `Category=gardening&Title:StartsWith=A&Created:LessThan=2014-11-11`

## Questions you may have

### What if I'm using a custom mailer, like Mandrill, or SendGrid?

No worries. This module doesn't override the mailer. It just overrides the `Email` class that wraps the Mailer.

### What about when the error logger sends out emails. I don't want to persist those to the database.

The error logger does not use the Email class unless you're unit testing. True errors are sent via simple PHP `mail()`.

### Won't this pollute the database? My site sends a lot of email.

For maintenance, use the `PermamailCleanupTask`. It accepts two parameters:
* `unit`: the unit of time to go back
* `count`: the number of units to go back

`/dev/tasks/PermamailCleanupTask?count=30&unit=days` will remove all emails that are more than 30 days old.

