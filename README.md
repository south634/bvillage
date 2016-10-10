# B Village
A Wordpress plugin experiment in clickjacking defense

## Background
A few years ago, I was considering building a blog ranking site in Japan. These sites work by counting incoming clicks from listed blogs, and ranking them in order of who sends the most clicks. The problem is how to deter fraudulent clicks.

This plugin was coded as an experiment to see how other Japanese blog ranking sites were deterring fake clicks. It works by loading a hidden iframe in the footer which makes it appear as if the visitor to your blog was referred to the site loaded in the iframe. In other words, though they did not click any link on your blog to visit the ranking site, it appears to the ranking site as if they had.

Most Japanese ranking sites count 1 click per IP address per day, and do not count clicks from non-Japanese IP addresses. This makes it nearly impossible for click bots to artificially boost ranks as Japanese IP addresses are extremely hard to come by. However, most also fail to block iframe embedding, which could be done for example with the use of the X-Frame-Options response header: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options

More reading on clickjacking defense here: https://www.owasp.org/index.php/Clickjacking_Defense_Cheat_Sheet

This plugin may be useful for anyone who wants to test their site's clickjacking defenses.

## Settings (WP admin panel: Settings > B Village)

### Target Click URL:
The URL where you want to send the "click" to.

### Block 1st Click From:
Enter the domain you want to block the 1st click from. Example: mytestrankingsite.com

This could be used if you're testing a ranking site that your blog is listed on. In that case, a visitor might click a link from the ranking site to visit your blog. This setting would prevent that referred visitor from instantly sending a click back to the originating ranking site.

Set the time you want to wait before allowing a "click" from this visitor in the "Referrer Click Delay" setting.

### Referrer Click Delay:
The amount of seconds you want to block loading the iframe for a visitor referred from the domain set in the "Block 1st Click From" setting.

### Visitor IP Click Delay:
This delay sets the amount of seconds to wait before allowing an iframe to be loaded for an IP address again. The default setting is 21600 seconds (6 hours).

### Global Click Delay (Seconds)
Delay (Min) | Delay (Max)

Sets a random click delay in seconds across the entire site for when the next iframe can be loaded.

Used to regulate click velocity on a high traffic site.

### Enable
Checkbox enables or disables plugin. Default is disabled.

## Other Plugin info

### iframe src URL
The plugin loads its iframe src as a PHP file (trak.php) instead of having the actual target URL visible in the source code. That PHP file then does a 301 redirect to the target URL. trak.php executes the 301 redirect only if a referrer is found containing the plugin's blog home URL.

### Checking for JavaScript
The plugin checks if JavaScript is enabled by creating a hidden form, submitting it with JavaScript, and checking if the 'jscheck' parameter isset in $_POST.

### If user logged in
The plugin checks if the visitor is currently logged into Wordpress, and will not load the iframe if so.

### Checking for common bot user agents
The plugin checks for some common bot strings in visitor's user agent, and does not load the iframe for them.
