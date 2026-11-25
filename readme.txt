=== HTTP Cache Integration ===
Tested up to: 7.1.5

Speed up webpages with HTTP entity tags and cache signals. Zero setup, self-contained, and won't touch your database.

== Description ==

Sets Cache-Control headers and generates weak ETags (`W/"..."`) for conditional `If-None-Match` (`304 Not Modified`) responses, without storing cached copies of your pages.

Combining HTTP cache directives with conditional requests is a powerful way to optimize server resources and make pages load lightning-fast without hitting your database. This plugin sets cache headers and lets WordPress efficiently signal whenever static content was updated, saving resources and gaining the efficiency benefits of a static site generator (SSG).

This is not a traditional caching plugin, but it lets you take better advantage of existing standards-compliant caching systems. It enables plug-and-play integration with multi-tier web caches, including drop-in support for global CDNs like Cloudflare, CloudFront, and Fastly.

Pages and posts are marked valid for 3 minutes by default, after which they are efficiently refreshed through a `304 Not Modified` response.

Private pages (logged in users, password-protected content, the admin dashboard, etc.) will not be cached.

== How it Works ==

Proper cache operation is critical to the performance and reliability of a website or web app. If there is no need for every request to reach the origin server, then the closer a cache is to a client, the faster the response will be. Avoiding repeated transfers of the same content reduces the amount of bandwidth required for both your servers and your site's visitors. In addition, when a response is reusable, the origin server can skip many processing steps. That reduces load on the server so it can respond faster and handle more requests at the same time.

= HTTP Caching =

HTTP caching stores the response associated with a request to reuse it for subsequent requests. Directives (specified in `Cache-Control`, `Vary`, `Expires`, etc.) indicate for how long and under what conditions the response can be reused. Entity tags (`ETags`) additionally represent the specific version of a resource, letting caches respond more efficiently. The origin server, or an intermediate cache, can send an empty `304 Not Modified` response to let the client know that its local copy (specified in `If-None-Match`) is still valid.

= Stateless Tag Generation =

This plugin statelessly derives a tag from information about the requested content and its host website. The tag is used as a weak validator in conditional requests. Weak validation indicates that the content is semantically equivalent and up-to-date, even if the data is not byte-for-byte identical. The tag validity is based on signals present in WordPress Core, including the post / page version, comments, and site parameters.

Tags are currently based on the following:

* The post's last modified date, including metadata changes
* The submission date of the latest comment
* Current theme's builtin styles and settings
* Versions of all active plugins
* Contents and layout of navigation menus
* Current site's title, tagline, and icon / logo
* All changes made in the Full Site Editor
