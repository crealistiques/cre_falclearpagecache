# Typo3 extension "cre_falclearpagecache"
Automatically clear the cache for pages which include changed/new files by uploads content element.


## Motivation ##

This extension aims to fill a gap in Typo3 Core functionality by means of clearing the cache for certain pages when files 
included on these pages were changed or even new files were added. 

Lets consider the case where you provide a PDF file for download by using the standard "uploads" (file links) content element.
By using the uploads content element you have quite a bunch of options to present the download link for the PDF file on
the frontend page. You could use the meta data title as link text, output the file size or accompany the link with a descriptive 
text generated from the description meta data field.

Supposing you cache the page where this content element lives on, which you normally should do (config.no_cache = 0), 
Typo3 provides no functionality so far to have the cache for that page cleared automatically when the referenced file 
changes in any way. Such a change to the file could be overwriting the file by a newer version using either the File list 
module in the Typo3 Backend or by uploading the new file over FTP. Or just imagine editing the meta data record of that file 
in the File list module, e.g. changing the title attribute field. 
  
All these type of changes would not be reflected on pages with references to our PDF file unless you manually clear the cache
for those pages one after another of by clearing the whole frontend page cache. I guess you get the point. This is where 
the extension steps in and provide a more efficient approach of handling changes to referenced files.

## What does it do? ##


## Installation ##

Just use the following command in your Typo3 document root to download the extension from Github. 

```
git clone "https://github.com/crealistiques/cre_falclearpagecache" typo3conf/ext/cre_falclearpagecache
```

Don't forget to install the extension in the Typo3 Extension Manager after fetching it.

## Usage ##

The extension itself relies on signals emitted from \TYPO3\CMS\Core\Resource\Index\FileIndexRepository as a trigger for
page cache clearing. In order for these signals to be emitted you should create a recurring scheduler task of type 
"File Abstraction Layer: Update storage index (scheduler)" with relatively high frequency according to your needs (e.g. 120 seconds).
That's all, you're done!

An additional option can be set in the extension configuration: "triggerMetaDataExtraction". If activated then meta data 
extraction is also triggered each time the extension checks the potential need for page cache clearing. This way newly 
added files by FTP to local or remote storages are also processed for meta data extraction just in time. 

A similar approach to achieve automatic meta data extraction for file system changes being made apart from the File list 
module is to utilise an additional scheduler task of type "File Abstraction Layer: Extract metadata in storage (scheduler)". 
The difference is that meta data extraction triggered by this extension (option "triggerMetaDataExtraction" set) is done 
right before the page cache clearing process, and therefore changes to file meta data are directly reflected the next time 
the page cache is build.        

## Features in depth ##

What does this extension do precisely:

* Automatically clear the cache of pages with one or more "uploads" type content elements when one or more files included
by those content elements are updated or added.
* Supports file inclusion in "uploads" content elements as normal file references in field "media".
* Supports file inclusion in "uploads" content elements as file collections of type folder!

> Please note: Support for file collections of type folder means that every time a new file is added to a folder based file 
collection caches of all pages referencing the respective file collections gets cleared.

## Todo ##
* Triggering page cache clearing after editing meta data in Typo3 backend File list module. 
* Support other file collection types: "static file collection" and "category based file collections"
* Consider a more general approach where even other content element types referencing files are supported. E.g. images referenced
by a textpic, image, textmedia or custom content element.
