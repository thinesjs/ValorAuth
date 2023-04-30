
# ValorAuth

A PHP Wrapper package for authenticating with Riot Unofficial APIs with MFA support.


## Installation

Install ValorAuth with composer.

```bash
composer require thinesjs/valor-auth:dev-master --ignore-platform-reqs
```
    
## Usage/Examples
Authenticate using riot username and password.
```php
use Thinesjs\ValorAuth\Authentication;

$authentication = new Authentication(["username"=>$request->username, "password"=>$request->password, "shard"=>"ap", "remember"=>true]);
$riotTokens = $authentication->authByUsername();
```


## Reference

 - [AuthSpace](https://github.com/weedeej/AuthSpace)



## License

[MIT](https://choosealicense.com/licenses/mit/)


## Legalities

Riot Games, VALORANT, and any associated logos are trademarks, service marks, and/or registered trademarks of Riot Games, Inc.

This project is in no way affiliated with, authorized, maintained, sponsored or endorsed by Riot Games, Inc or any of its affiliates or subsidiaries.

I, the project owner and creator, am not responsible for any legalities that may arise in the use of this project. Use at your own risk.

