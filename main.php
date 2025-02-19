<?php

// ConsoleApplication PHP class
class ConsoleApplication
{
    // main instance method , an entry-point for the application
    public function main()
    {
        // ask for user input
        echo "Enter something: ";
        // read the input
        $input = readline();

        // display the input
        echo "You entered: '$input'\n";

    }
}

// create an instance of the ConsoleApplication class and call the main method
$application = new ConsoleApplication();
// call the main method , an entry-point for the application
$application->main();
