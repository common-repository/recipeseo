=== RecipeSEO ===
Contributors: codeswan
Donate link: http://sushiday.com/recipe-seo-plugin/
Tags: recipes, seo, hrecipe, Recipe View
Requires at least: 3.1
Tested up to: 3.1
Stable tag: "trunk"

Recipe SEO made simple. Formats your recipes with the appropriate microformats, so they are more likely to appear in Google's Recipe View.

== Description ==

With the introduction of [Google's Recipe View](http://googleblog.blogspot.com/2011/02/slice-and-dice-your-recipe-search.html), suddenly microformats became incredibly important to food bloggers. If you don't use microformats for your recipes (or microdata, for those using HTML5), then your blog most likely won't show up in Recipe View searches.

But most people don't want to spend the time and effort to hand-code microformats into their recipes every single time they publish a blog post. It's a lot of work, and quite frankly a pain in the rear, especially if you're not familiar with HTML.

That's where this plugin comes in.

The RecipeSEO Plugin gives you the power to take full advantage of the benefits of microformats, without having to deal with HTML and the messy microformat code at all. All you have to do is enter the information about your recipe, and the plugin will automatically add all the necessary code to your recipe.

It's quick. It's simple. And best of all, your recipes now have a much better chance of showing up in Google's Recipe View, with very little additional work from you!

(If you don't use WordPress.org, or would rather be able to edit the formatted HTML on your own, check out my [RecipeSEO App!](http://recipeseo.com/))

== Installation ==

You can download and install the RecipeSEO Plugin using the built in WordPress plugin installer. If you download the RecipeSEO Plugin manually, make sure it is uploaded to "/wp-content/plugins/recipeseo/".

Activate the RecipeSEO Plugin in the "Plugins" admin panel using the "Activate" link.

To use the plugin, click the [little RecipeSEO icon](http://sushiday.com/wp-content/themes/sushiday/images/recipeseo.gif) on the "Edit Post" pages, right next to the other "Upload/Insert" links at the top of the text editor box. Then enter the details about your recipe into the appropriate boxes, and then click the "Add Recipe" button. This will save your recipe, and insert it into your blog post.

== Frequently Asked Questions ==

= Why do you put a placeholder image into my Edit Post page, instead of my actual recipe? =

Because of the way WordPress' text editor works, if you decide to add or remove something from your recipe using the text editor, it can very easily mess up the markup of the code - so the RecipeSEO plugin prevents that from happening by not allowing you to edit the recipe in the text editor. Although currently there is no way to edit an existing recipe, the next version of the RecipeSEO plugin (which will be released in a few days) will have the functionality to do so.

= What if my site is in HTML5? =

We will have a version that uses microdata (instead of microformats) for websites that use HTML5 very soon! But for now, the microformats that we use should work just fine for all HTML5 sites.

= How can I request a feature to be added in future versions of the RecipeSEO Plugin? =

You can [contact me](http://sushiday.com/contact/) with your requests.  I may not be able to implement all requests, but I will definitely take your suggestions into consideration.

== Screenshots ==

1. The RecipeSEO Plugin icon is located next to the other "Upload/Insert" media icons.
2. It's easy to enter the basic information for your recipes: the title, the ingredients, and the instructions for preparing the recipe.
3. There is no limit on the number of ingredients you can add.
4. And if you want to add even more information about your recipe, such as your rating of the recipe, or the serving size, all you have to do is click the "More Options" link, and you can!
5. You can fill out as many or as few additional options as you would like.
6. Once you click the "Add Recipe" button, a placeholder image will be inserted into your post where your recipe will go.
7. Once you preview or publish the post, your recipe will be there with all your microformats... without any extra work from you!
8. Voila! Your new recipe can easily be styled with CSS, to look however you would like.
9. But what if you want to make changes to the recipe you just entered?  All you have to do is click on the placeholder image, and then click on the big fat edit image (the left-hand one).
10. Make your changes and click the "Update Recipe" button...
11. Edited!  Easy as can be.

== Changelog ==

1.1 Fixed a bug that was keeping the placeholder image from being replaced with the correct recipe information.

1.1.1 Fixed a bug that was erasing everything following apostrophes in the input fields.

1.1.2 Fixed the bug that was keeping the plugin from working with the HTML post editor.

1.2 Added edit functionality!

1.3 Added ability to choose the format for the lists of ingredients and instructions. Added option to change or remove all of the labels. Added an 'Are you sure you want to delete?' alert when the user clicks on the delete recipe button. Fixed Prep Time, Cook Time, and Total Time so it's easy to select and display the times in proper ISO 8601 formats.

1.3.1 Fixed bugs that were throwing errors if the user isn't running the latest version of PHP.

1.3.2 Fixed the bug that was a result of the WP 3.5 update, causing the placeholder image to show up in final posts.

== Upgrade Notice ==

1.2 Users can now edit recipes that they have already entered.

1.3 Users can now choose what format they want their ingredients and instructions in, as well as change or remove all of the labels. Times will now display in ISO 8601 formats.

== Coming Soon... ==

**Features that will be added in upcoming versions of the RecipeSEO Plugin:**

* Custom recipe styling options
* Photos in recipes
* Multi-part recipes
* **Have a suggestion for a feature I should add? [Tell me!](http://sushiday.com/contact/)**