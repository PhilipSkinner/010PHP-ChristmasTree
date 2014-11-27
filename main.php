<?php

$genes = array(
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
  new Gene(),
);

$best = $genes[0];

while (1) {
  for ($j = 0; $j < sizeof($genes); $j++) {
    $genes[$j]->mutate();
    
    $fitness = $genes[$j]->determineFitness();
    
    if ($fitness > $best->fitness) {
      $best = $genes[$j];
    }
    
    if ($fitness === 1) {
      echo $genes[$j]->decode();
      
      return;
    }
  }
  
  #then we breed
  if ($best) {
    echo $best->decode();
    for ($j = 0; $j < sizeof($genes); $j++) {
      if ($best != $genes[$j]) {
        $genes[$j]->merge($best);
      }
    }
  }
}
  
class Gene {
  public $string = "";
  public $rows = 8;
  public $cols = 8;
  public $mutationRate = 0.0001;
  public $crossoverRate = 0.001;
  public $fitness = 0;

  private $commands = array(
    "0000" => " ",
    "0001" => "/",
    "0010" => "\\",
    "0011" => "o",
    "0100" => " ",
    "0101" => " ",
    "0110" => " ",
    "0111" => " ",
    "1000" => " ",
    "1001" => " ",
    "1010" => " ",
    "1011" => " ",
    "1100" => " ",
    "1101" => " ",
    "1110" => " ",
    "1111" => " ",
  );
  
  public function __construct() {
    $this->disinfect();
  }
  
  public function disinfect() {
    $this->string = "";
    for ($i = 0; $i < $this->rows * $this->cols; $i++) {
      $this->string .= "0000";
    }  
  }
  
  public function decode() {
    $ret = "";
    for ($i = 0; $i < $this->rows * $this->cols; $i++) {
      $part = substr($this->string, $i, 4);
    
      $ret .= $this->commands[$part];
    
      if ($i % $this->cols == 0) {
        $ret .= "\n";
      }
    }
    
    return $ret . "\n";
  }
  
  public function determineFitness() {
    $this->fitness = 1;
    
    $partCount = array(
      "0000" => 0,
      "0001" => 0,
      "0010" => 0,
      "0011" => 0,
      "0100" => 0,
      "0101" => 0,
      "0110" => 0,
      "0111" => 0,
      "1000" => 0,
      "1001" => 0,
      "1010" => 0,
      "1011" => 0,
      "1100" => 0,
      "1101" => 0,
      "1110" => 0,
      "1111" => 0,
    );
    
    #this bit could be shorter
    for ($i = 0; $i < $this->rows * $this->cols; $i++) {
      $part = substr($this->string, $i, 4);
      
      $partCount[$part]++;
      
      #create our adjacent list for checking, starts at top and goes clockwise
      $list = array(
        "0000",
        "0000",
        "0000",
        "0000",
        "0000",
        "0000",
        "0000",
        "0000",
      );
      
      #which row/col are we on?
      $row = floor($i / $this->cols);
      $col = floor($i % $this->rows);
      
      $upwards = 0;
      $downwards = 0;
      $left = 0;
      $right = 0;
      
      if ($row > 0) {
        $upwards = 1;
      }
      if ($row < $this->rows - 1) {
        $downwards = 1;
      }
      
      if ($col > 0) {
        $left = 1;
      }
      if ($col < $this->cols -1) {
        $right = 1;
      }
      
      #now we determine the codes in these positions
      if ($upwards) {
        #this is position 0
        $list[0] = substr($this->string, $i - ($this->cols * 4), 4);
        
        if ($left) {
          #this is position 7
          $list[1] = substr($this->string, $i - ($this->cols * 4) - 4, 4);
        }
        
        if ($right) {
          #this is position 1
          $list[7] = substr($this->string, $i - ($this->cols * 4) + 4, 4);
        }        
      }
      
      if ($downwards) {
        #this is position 4
        $list[4] = substr($this->string, $i + ($this->cols * 4), 4);
      
        if ($left) {
          #this is position 5
          $list[5] = substr($this->string, $i + ($this->cols * 4) - 4, 4);
        }
        
        if ($right) {
          #this is position 3
          $list[3] = substr($this->string, $i + ($this->cols * 4) + 4, 4);
        }
      }
      
      if ($left) {
        #this is position 6
        $list[6] = substr($this->string, $i - 4, 4);
      }
      
      if ($right) {
        #this is position 2
        $list[2] = substr($this->string, $i + 4, 4);
      }
      
      #now we need to check for 2 particular types
      
      if ($part === "0001") { #checking /
        if ($list[0] === "0001" || $list[0] === "0010" || $list[0] === "0011") {
          $this->fitness -= 0.05;
        }
        if ($list[1] === "0010" || $list[1] === "0011") {
          $this->fitness -= 0.05;
        }
        if ($list[2] === "0001") {
          $this->fitness -= 0.05;
        }
        if ($list[3] === "0001" || $list[3] === "0010") {
          $this->fitness -= 0.05;
        }
        if ($list[4] === "0001" || $list[4] === "0010") {
          $this->fitness -= 0.05;
        }
        if ($list[5] === "0010" || $list[5] === "0011") {
          $this->fitness -= 0.05;
        }
        if ($list[6] === "0001" || $list[6] === "0010" || $list[6] === "0011") {
          $this->fitness -= 0.05;
        }
        if ($list[7] === "0001" || $list[7] === "0010" || $list[7] === "0011") {
          $this->fitness -= 0.05;
        }

        #we can't be all on our own, ensure atleast 1 continuation of the line
        if ($list[1] !== "0001" && $list[5] != "0001") {
          $this->fitness -= 0.1;
        }
      } else if ($part === "0010") { #checking \
        if ($list[0] === "0001" || $list[0] === "0010" || $list[0] === "0011") {
          $this->fitness -= 0.05;        
        }
        if ($list[1] === "0001" || $list[1] === "0010" || $list[1] === "0011") {
          $this->fitness -= 0.05;        
        } 
        if ($list[2] === "0001" || $list[2] === "0010" || $list[2] === "0011") {
          $this->fitness -= 0.05;        
        }
        if ($list[3] === "0001" || $list[3] === "0011") {
          $this->fitness -= 0.05;        
        }
        if ($list[4] === "0010" || $list[4] === "0001") {
          $this->fitness -= 0.05;        
        }
        if ($list[5] === "0010" || $list[5] === "0001") {
          $this->fitness -= 0.05;        
        }
        if ($list[6] === "0010") {
          $this->fitness -= 0.05;        
        }
        if ($list[7] === "0001" || $list[7] === "0011") {
          $this->fitness -= 0.05;        
        }

        #we can't be all on our own, ensure atleast 1 continuation of the line
        if ($list[7] !== "0010" && $list[3] != "0010") {
          $this->fitness -= 0.1;
        }
      }      
    }
    
    #finally, check our part count
    
    if ($partCount["0001"] < 2) { #we want atleast two /
      $this->fitness -= 0.1;
    }
    if ($partCount["0010"] < 2) { #we want atleast two \
      $this->fitness -= 0.1;
    }
    
    if ($this->fitness < -2) {
      #eugh, our genome is fugly
      $this->disinfect();
    }
    
    return $this->fitness;
  }
  
  public function merge($partner) {
    $ourParts = str_split($this->string);
    $theirParts = str_split($partner->string);
    
    for ($i = 0; $i < sizeof($ourParts); $i++) {
      $chance = mt_rand() / mt_getrandmax();
      if ($chance < $this->crossoverRate) {
        $tmpOurs = $ourParts[$i] . "";
        $tmpTheirs = $theirParts[$i] . "";
        
        $ourParts[$i] = $tmpTheirs;
        $theirParts[$i] = $tmpOurs;
      }
    }
    
    $this->string = implode($ourParts);
    $partner->string = implode($theirParts);
  }
  
  public function mutate() {
    $parts = str_split($this->string);      
  
    for ($i = 0; $i < sizeof($parts); $i++) {
      $chance = mt_rand() / mt_getrandmax();      
      if ($chance < $this->mutationRate) {
        if ($parts[$i] === "0") {
          $parts[$i] = "1";
        } else {
          $parts[$i] = "0";
        }
      }
    }
    
    $this->string = implode($parts);
  }    
}
?>
