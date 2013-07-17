# Crazy Bone Analyzer

Analyzes illegal access data retrieved with WordPress plugin [Crazy Bone]( http://wordpress.org/plugins/crazy-bone/ )

## Installation

1. [Download Zip](https://github.com/msng/crazy-bone-analyzer/archive/master.zip) and unarchive it, or `git clone https://github.com/msng/crazy-bone-analyzer.git`
1. `cd crazy-bone-analyzer`
1. Get composer.  
`curl -s http://getcomposer.org/installer | php`
1. Install components via composer.  
`php composer.phar install`
1. Make `compilation\_cache/` directory writable by PHP.
1. Copy `config.php.default` to `config.php` and edit the config file to fit your WordPress database setting.
1. Enjoy.

