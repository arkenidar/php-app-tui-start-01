# Project TextUI in PHP
# with TCP servers added

A brief description of what this project does and who it's for. ( follows below )

## who is for ?
- Learners of PHP , learners of HTTP , learners of SQL . Learning Material .
- Esperimenters of embedded ideas demonstrated you can tweak , copy and combine .
- Basically useful knowledge nuggets shared in simple cases and simple demos yet very significant and valuable ( as I see and intend them ) .

## apps , applications

PHP servers both HTTP protocol and other custom protocol even very basic and simple .
Built with PHP Fibers and PHP NonBlocking Network InPut & OutPut ( I.O. ) .

### app server-http

Server that serves php scripts in a long-running process context that means in its own peculiar way to circumvent the limitations of http being a state-less protocol .
Kind of a seed of a PHP specialized Application Server .

### app todo-unified
A demonstration of php tcp server usable by any tcp client if wanted ( for input and output , like GNU NetCat ) . defaults to console input and output .
this actually does some basic sql operations eased via redbeanphp-orm .

![Screenshot](orm-redbean/tui_orm_netcat_Peek_23-02-2025_20-06.gif)

### Installation

Instructions on how to install and run the project.

PHP CLI is needed .
Run ```php todo-unified.php tcp```
for TCP server ...

... and ```nc localhost 8080``` for TCP client

## Usage

Examples of how to use the project contents ( many apps ) .

- app: *todo-unified*.php / combined TUI both direct and via TCP connection via a TCP client like netcat ( "nc" )
    ```bash
    cd ~/Dropbox/git/php-apps-text-ui/php-app-tui-start-01/orm-redbean && php todo-unified.php tcp &

    rlwrap nc localhost 8080
    ```
- app: *server-http*.php
    HTTP Server for PHP Applications that in a long-running process there is a pratical possibility of keeping state across the request response cycle in a direct way : you are *enabled* to keep any PHP objects in ( same process ) memory . Stateful so to speak , wheres Classic HTTP is state-less with some ways to circumvent like Redis . This is a way to circumvent as added possibility besides previously existing .

## Contributing

Contact me with some message , any message : I'll do my best to create a kind and meaningful interaction .
- <https://arkenidar.com>
- <dario.cangialosi@gmail.com>

## License

The project's license is a liberal MIT license.