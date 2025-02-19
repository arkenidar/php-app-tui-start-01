<?php

// ConsoleApplication PHP class
class ConsoleApplication
{
    // main instance method , an entry-point for the application
    public function main()
    {
        // call the readInput method
        // $this->readInput();

        // call the sortNumbersFromInput method
        $this->sortNumbersFromInput();
    }

    // method to read user input
    public function readInput()
    {
        // ask for user input
        echo "Enter something: ";
        // read the input
        $input = readline();

        // display the input
        echo "You entered: '$input'\n";
    }

    // method to sort a list of numbers
    public function sortNumbersFromInput()
    {
        // ask numbers
        echo "Enter numbers separated by space: ";
        // read the input
        $input = readline();
        // split the input into an array of numbers
        $numbers = explode(" ", $input);
        // remove empty elements
        $numbers = array_filter($numbers, function ($value) {
            return $value !== '';
        });
        // remove non-numeric elements
        $numbers = array_filter($numbers, function ($value) {
            // is value a number?
            // is_numeric() function checks if a variable is a number or a numeric string
            if (!is_numeric($value)) {
                // display an error message
                echo "Error: '$value' is not a number\n";
                // return false to remove the element
                return false;
            }
            // return true to keep the element
            return true;
        });
        // convert the numbers to integers
        array_walk($numbers, function (&$value) {
            // convert the value to an integer
            $value = (int)$value;
        });

        // sort the numbers
        sort($numbers);

        // display the sorted numbers
        echo "Sorted numbers: [ ";
        foreach ($numbers as $number) {
            echo "$number ";
        }
        echo "]\n";
    }
}

// create an instance of the ConsoleApplication class and call the main method
$application = new ConsoleApplication();
// call the main method , an entry-point for the application
$application->main();
