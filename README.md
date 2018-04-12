Google Docs
===========

This is a TYPO3 CMS extension which allows FE user to create and edit Google
documents.

Features
--------

-   Authenticate a Google user via the oAuth 2.0 mechanism

-   Manage Google Docs such as documentation creation, deletion

-   Set permission against a document such as "writer", "commenter" and "reader"

-   Display a document to be seen / edited within a secure iframe

-   FE plugin to display the permission matrix.

-   Connect a FE User with a Google email address.

Installation
------------

-   Install the extension in the EM as usually

-   Create a FE User Group: "Secretary" and "Reviewer"

    -   The group "secretary" is considered as admin

    -   The group "reviewer" is only allowed to create comments
    
-   `composer require google/apiclient`

Configure Google Project
------------------------

-   If not already existing, create a new project in
    <https://console.developers.google.com/>

-   Enable a new Application API. This
    [wizard](https://console.developers.google.com/flows/enableapi?apiid=sheets.googleapis.com)
    can be used to enable the API and generate the credentials.

-   Upload and customize the `.secret/id_client.json` path in the EM settings.

Security concern
----------------

Makes sure file `.secret/id_client.json` and within
`typo3temp/google_api_credentials/*` are secure and can not be served by the
server.

Demo Google Accounts
--------------------

-   **Admin**: fabien.temporary@gmail.com  
    password: CpnihxpzXzkaujyCTztuRnm9hpgZJrvB

-   **Reviewer**: ignace.temporary@gmail.com  
    password: eXfQBsUfZupkMGNQXPtC4mMJWVWwycMu

Links
-----

-   Remove User Credentials  
    <https://console.cloud.google.com/iam-admin/iam/project?project=gichd-192320>

-   Remove credentials from its account  
    <https://myaccount.google.com/permissions>

Google Services
---------------

The extension is exposing a set of API that wraps the low level Google REST API.
As a developer, you should check inside the GoogleDocumentController to see how
the services are wired together.

The extension is adding two fields to the table `fe_users` one is named
`connected_google_email` to connect the google email address to the FE User, the
other is `google_permission_id` to store the Google permission id which is
generated when setting create a new ACL against a file. The
`google_permission_id` is hidden and is automatically filled when creating
permissions.

Dependency with google/apiclient package
----------------------------------------

This extension requires the package `google/apiclient` to be loaded. This
library is taking care of the low level communication with the Google services
via a REST API. More info about the API
[here](https://developers.google.com/drive/v3/reference/). If the TYPO3 website
is not supporting Composer, one can install and load this package within a TYPO3
extension. To give an idea:

```
mkdir -p typo3conf/ext/composer_packages/Resources/Private/PHP
cd typo3conf/ext/composer_packages/Resources/Private/PHP
composer require google/apiclient:^2.0
```