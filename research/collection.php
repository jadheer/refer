<?php
 class ObjectCollection
{
    //This is an array to hold line items
    private $items_array ;

    private $itemCounter; //Count the number of items

    public function __construct() {
        //Create an array object to hold line items
        $this->items_array = array();
        $this->itemCounter=0;
     }

    public function getItemCount(){
        return $this->itemCounter;
    }

    public function getItems(){
        return $this->items_array;
    }

    // This will add a new line object to line items array
    public function addItem($item) {
       $this->itemCounter++;
       $this->items_array[] = $item;
    }

}


class car {
  private $id;
  private $description;
  private $topspeed;
  private $price;

  public function __construct($id, $price) {
       $this->id = $id;
       $this->price = $price;
  }

  public function setDescription($description) {
            $this->description = $description ;
  }

  public function getDescription() {
      return $this->description ;
  }

  public function setTopspeed($topspeed) {
            $this->topspeed = $topspeed;
 }

public function getTopspeed() {
      return $this->topspeed ;
 }

 //other methods here

} //End of class

 $car = new car("1",400);
 $car2 = new car("2",4400);

 $car->setDescription("A really fast car ");
 $car2->setDescription("A really slow car ");



 $ObjColl = new ObjectCollection();
 $ObjColl->addItem($car);
 $ObjColl->addItem($car2);



//  for($i = 0;$ObjColl->getItemCount();$i++){
//    //CODE NEED TO BE ADDED TO OUTPUT TOPSPEED AND DESCRIPTION ECT????
// }


// foreach ($ObjColl as $coll) {
//     var_dump($coll);
// }

foreach ($ObjColl->getItems() as $item){
    if ($item  instanceof car){
        echo $item->getDescription();
    }
}
