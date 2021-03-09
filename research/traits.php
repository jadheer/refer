<?php

// trait message1 {
// public function msg1() {
//     echo "OOP is fun! ";
//   }
// }

// trait message2 {
// public function msg2() {
//     echo "OOP is fun2! ";
//   }
// }

// class Welcome {
//   use message1;
//   use message2;
// }

// $obj = new Welcome();
// $obj->msg1();
// $obj->msg2();
?>

<?PHP
class Tree
{
  // function Tree()
  // {
  //   echo "Its a User-defined Constructor of the class Tree";
  // }

  function __construct()
  {
    echo "Its a Pre-defined Constructor of the class Tree";
  }

        function __destruct()
        {
            echo "destroying 1". "\n";
        }

}

class Tree2 extends Tree
{
  // function Tree()
  // {
  //   echo "Its a User-defined Constructor of the class Tree";
  // }

  function __construct()
  {
    echo "Its a Pre-defined Constructor of the class Tree2";
  }

        function __destruct()
        {
            echo "destroying 2". "\n";
        }

}

$obj= new Tree2();
?>
