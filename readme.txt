=== Cache Posts and Pages ===
Tested up to: 7.1.5

Enables browsers, web servers, and CDNs to cache responses for unmodified content, reducing server bandwidth and think time.

== Description ==

This plugin optimizes resource usage and accelerates load times by sending cache signals. It enables browsers, web servers, and CDNs to cache responses for unmodified content, reducing server bandwidth and think time.

= Entity Tags =

Entity tags (ETags) are used to represent the specific version of a resource, letting caches respond more efficiently and save bandwidth. This plugin uses the equivalence of content to derive a semantic tag used for weak validation (a Weak ETag). If the requested tag matches, the WordPress origin will skip rendering the document body and return a `304 Not Modified`. That saves on server compute and lets clients revalidate their caches quickly with minimal transferred data.

The tag is based on signals present in WordPress Core, including the content version, comments, and site parameters. Weak ETags are invalidated by any settings or content changes that can alter the returned text, its visual appearance, or the site's layout and functionality.

Weak ETags are currently derived from the following signals:

* The post's last modified date, including metadata changes
* The submission date of the latest comment
* Current theme's builtin styles and settings
* Versions of all active plugins
* Contents and layout of navigation menus
* Current site's title, tagline, and icon / logo
* All changes made in the Full Site Editor

= Cache Directives =

Responses are marked as valid for 1 minute by default using cache directives (Cache-Control and Vary headers). During that time, browser and CDN caches can use their local copy immediately without any requests to the origin. After that, they will revalidate the response using its last known ETag. Content only needs to be rendered and transferred if its ETag changed since the last cache validation, signalled through the If-None-Match header.

Caches are disabled if a user is logged in to ensure real-time feedback and interactive features continue to work properly. Login prompts and administrator dashboard menus are never cached.
