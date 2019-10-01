## Prerequisites
* php v7+
* php-cli
* composer

## Usage
* `git clone https://github.com/xidb/youtube-subs-dl youtube-subs-dl`
* Run `cd youtube-subs-dl && composer install && cd ..` 
* Download latest <a href="https://rg3.github.io/youtube-dl/">youtube-dl</a> build and put it in `youtube-subs-dl` directory or make it available globally
* (optional) Do the same thing with <a href="https://www.ffmpeg.org/download.html">ffmpeg</a>
* To download your subscribtions list visit <a href="https://www.youtube.com/subscription_manager?action_takeout=1">this link</a> logged in to Youtube and save file as `youtube-subs-dl/subscribtions.xml`
* Run `php youtube-subs-dl/update.php`. By default it will download videos for the last 3 days. You may change it by inserting desired timestamp in `youtube-subs-dl/last.txt`. After that it will download videos after last check.
