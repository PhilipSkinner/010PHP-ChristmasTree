<?php

/*
  Written by Philip Skinner (me@philip-skinner.co.uk).

  This was written for a presentation on genetic algorithms for 010PHP, a PHP meetup group based in Rotterdam.
  
  This code is free, though I don't know why you'd want to steal any of it. It doesn't really do anthing useful.
*/

#always a good thing to be doing, honest
error_reporting(E_ERROR | E_PARSE);

#lets create some genes
$numgenes = 10;
$genes = array();
for ($i = 0; $i < $numgenes; $i++) {
  array_push($genes, new Gene());
}

#our best is currently our first
$best = $genes[0];

#until we have a solution...
while (1) {
  for ($j = 0; $j < sizeof($genes); $j++) {
    #mutate each gene
    $genes[$j]->mutate();
    #duplicate the good parts of the gene
    $genes[$j]->duplicate();
    
    #determine our fitness
    $fitness = $genes[$j]->determineFitness();
    
    if ($fitness > $best->fitness) {
      #we're the master of the genepool!
      $best = $genes[$j];
    }
    
    #our gene is the best
    if ($fitness === 1) {
      echo $genes[$j]->decode();
      
      #and we're done!
      return;
    }
  }
  
  #then we breed our best with the rest
  if ($best) {
    echo $best->decode();
    for ($j = 0; $j < sizeof($genes); $j++) {
      if ($best != $genes[$j]) {
        #NSFW
        $genes[$j]->merge($best);
      }
    }
  }
}

/* 
  Gene class has the base function of striving to evolve into a Christmas Tree.
  
  Constructor takes:
    rows 		:- Number of rows to use when drawing the tree
    cols 		:- Number of columnss to use then drawing the tree
    mutationRate 	:- A number less than 1, dictates the chance of a random mutation happening
    crossoverRate	:- A number less than 1, dictates the chance of a mutation via breeding
    duplicationRate	:- A number less than 1, dictates the spread of similar genes (duplicates) through the system
    correctionRate	:- A number less than 1, dictates the antibody effect on cleaning up bad genes

  Methods available:
    mutate		:- Mutates the gene randomly
    duplicate		:- Duplicates good parts of the gene in the right places
    determineFitness	:- Determines the fitness of the gene, a value of 1 means its perfect
    decode		:- Returns a human readable version of the gene for printing
    merge		:- Takes another gene as an argument, breeds the genes together
    disinfect		:- Say goodbye to your gene! Resets it.
*/
  
class Gene {
  public $string = "";
  public $rows;
  public $cols;
  public $mutationRate;
  public $duplicationRate;
  public $correctionRate;
  public $crossoverRate;
  public $fitness = 0;

  #how we encode our binary genetic strings into display values
  private $commands = array(    
    "0000" => "*",
    "0001" => "*",
    "0010" => "*",
    "0011" => "o",
    "0100" => "*",
    "0101" => "*",
    "0110" => "*",
    "0111" => "*",
    "1000" => "*",
    "1001" => "*",
    "1010" => "*",
    "1011" => "*",
    "1100" => "*",
    "1101" => "/",
    "1110" => "\\",
    "1111" => "*",
  );

  #duplication weighting arrays  
  private $duplications = array(
    "0000" => [0,0,0,0,0,0,0,0],
    "0001" => [0,0,0,0,0,0,0,0],
    "0010" => [0,0,0,0,0,0,0,0],
    "0011" => [0,0,0,0,0,0,0,0],
    "0100" => [0,0,0,0,0,0,0,0],
    "0101" => [0,0,0,0,0,0,0,0],
    "0110" => [0,0,0,0,0,0,0,0],
    "0111" => [0,0,0,0,0,0,0,0],
    "1000" => [0,0,0,0,0,0,0,0],
    "1001" => [0,0,0,0,0,0,0,0],
    "1010" => [0,0,0,0,0,0,0,0],
    "1011" => [0,0,0,0,0,0,0,0],
    "1100" => [0,0,0,0,0,0,0,0],
    "1101" => [-1,1,0,-1,-1,1,-1,-1],
    "1110" => [-1,-1,-1,1,-1,-1,0,1],
    "1111" => [0,0,0,0,0,0,0,0],
  );
  
  #our strong chains, these can't be cleaned up
  private $strongChains = array(
    '1101',
    '1110',
  );
  
  public function __construct($rows=8, $cols=8, $mutationRate=0.001, $crossoverRate=0.0001, $duplicationRate=0.003, $correctionRate=0.5) {
    $this->rows 		= $rows;
    $this->cols 		= $cols;
    $this->mutationRate 	= $mutationRate;
    $this->crossoverRate 	= $crossoverRate;
    $this->duplicationRate	= $duplicationRate;
    $this->correctionRate 	= $correctionRate;
    
    #we disinfect to get started
    $this->disinfect();
  }
  
  public function disinfect() {
    $this->string = "";
    #creates an empty string for our gene
    for ($i = 0; $i < $this->rows * $this->cols; $i++) {
      $this->string .= "0000";
    }  
  }
  
  public function decode() {
    $ret = "";
    for ($i = 0; $i < $this->rows * $this->cols; $i++) {
      $part = substr($this->string, $i * 4, 4);
    
      $ret .= $this->commands[$part];
    
      if (($i + 1) % $this->cols == 0) {
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
      $part = substr($this->string, $i * 4, 4);
      
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
        $list[0] = substr($this->string, ($i * 4) - ($this->cols * 4), 4);
        
        if ($left) {
          #this is position 7
          $list[1] = substr($this->string, ($i * 4) - ($this->cols * 4) - 4, 4);
        }
        
        if ($right) {
          #this is position 1
          $list[7] = substr($this->string, ($i * 4) - ($this->cols * 4) + 4, 4);
        }        
      }
      
      if ($downwards) {
        #this is position 4
        $list[4] = substr($this->string, ($i * 4) + ($this->cols * 4), 4);
      
        if ($left) {
          #this is position 5
          $list[5] = substr($this->string, ($i * 4) + ($this->cols * 4) - 4, 4);
        }
        
        if ($right) {
          #this is position 3
          $list[3] = substr($this->string, ($i * 4) + ($this->cols * 4) + 4, 4);
        }
      }
      
      if ($left) {
        #this is position 6
        $list[6] = substr($this->string, ($i * 4) - 4, 4);
      }
      
      if ($right) {
        #this is position 2
        $list[2] = substr($this->string, ($i * 4) + 4, 4);
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
    
    if ($this->fitness < -10) {
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
  
  public function duplicate() {
    for ($i = 0; $i < $this->rows * $this->cols; $i++) {
      $part = substr($this->string, $i * 4, 4);
      
      #now we determine if we should positively or negatively correct an adjacent cell in the organism
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
        $list[0] = substr($this->string, ($i * 4) - ($this->cols * 4), 4);
        
        if ($left) {
          #this is position 7
          $list[1] = substr($this->string, ($i * 4) - ($this->cols * 4) - 4, 4);
        }
        
        if ($right) {
          #this is position 1
          $list[7] = substr($this->string, ($i * 4) - ($this->cols * 4) + 4, 4);
        }        
      }
      
      if ($downwards) {
        #this is position 4
        $list[4] = substr($this->string, ($i * 4) + ($this->cols * 4), 4);
      
        if ($left) {
          #this is position 5
          $list[5] = substr($this->string, ($i * 4) + ($this->cols * 4) - 4, 4);
        }
        
        if ($right) {
          #this is position 3
          $list[3] = substr($this->string, ($i * 4) + ($this->cols * 4) + 4, 4);
        }
      }
      
      if ($left) {
        #this is position 6
        $list[6] = substr($this->string, ($i * 4) - 4, 4);
      }
      
      if ($right) {
        #this is position 2
        $list[2] = substr($this->string, ($i * 4) + 4, 4);
      }
      
      #pretty simple
      $singleParts = str_split($part);
      $duplications = $this->duplications[$part];
      for ($j = 0; $j < sizeof($duplications); $j++) {
        if ($part != $list[$j]) {
          if ($duplications[$j] == 1) {
            #merge them together positively          
            
            #hack
            $chance = mt_rand() / mt_getrandmax();
            if ($chance < $this->duplicationRate) {
              $list[$j] = $part;
            }
          } else if ($duplications[$j] == -1) {
            #merge them together negatively
            
            $chance = mt_rand() / mt_getrandmax();
            if ($chance < $this->correctionRate) {
              if (!in_array($list[$j], $this->strongChains)) {
                #we kill everything!              
                $list[$j] = '0000';
              }
            }
          }
        }
      }   
      
      #then we need to regenerate our whole string
      if ($upwards) {
        #this is position 0
        $this->string = substr_replace($this->string, $list[0], ($i * 4) - ($this->cols * 4), 4);
        
        if ($left) {
          #this is position 7
          $this->string = substr_replace($this->string, $list[1], ($i * 4) - ($this->cols * 4) - 4, 4);
        }
        
        if ($right) {
          #this is position 1
          $this->string = substr_replace($this->string, $list[7], ($i * 4) - ($this->cols * 4) + 4, 4);
        }        
      }
      
      if ($downwards) {
        #this is position 4
        $this->string = substr_replace($this->string, $list[4], ($i * 4) + ($this->cols * 4), 4);
      
        if ($left) {
          #this is position 5
          $this->string = substr_replace($this->string, $list[5], ($i * 4) + ($this->cols * 4) - 4, 4);
        }
        
        if ($right) {
          #this is position 3
          $this->string = substr_replace($this->string, $list[3], ($i * 4) + ($this->cols * 4) + 4, 4);
        }
      }
      
      if ($left) {
        #this is position 6
        $this->string = substr_replace($this->string, $list[6],  ($i * 4) - 4, 4);
      }
      
      if ($right) {
        #this is position 2
        $this->string = substr_replace($this->string, $list[2], ($i * 4) + 4, 4);
      }         
    }
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
