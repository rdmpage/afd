<?php

/**
 * @file qt.php
 *
 * Quantum treemap
 *
 * See "Ordered and quantum treemaps: Making effective use of 2D space to display hierarchies"
 * doi:10.1145/571647.571649 (PDF http://hcil.cs.umd.edu/trs/2001-18/2001-18.pdf)
 * and http://www.cs.umd.edu/local-cgi-bin/hcil/sr.pl?number=2001-10
 *
 * Code is a PHP port of Java code available from 
 * http://www.cs.umd.edu/hcil/photomesa/download/layout-algorithms.shtml
 *
 */


$offset_y = 0;

$debug = false;

if ($debug) { $offset_y = 200; }

//--------------------------------------------------------------------------------------------------
/**
 * @brief Encapsulate a rectangle
 *
 */
 class Rectangle
{
	var $x;
	var $y;
	var $w;
	var $h;
	
	function __construct($x=0, $y=0, $w=0, $h=0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->w = $w;
		$this->h = $h;
	}
	
	function Dump()
	{
		echo "[" . $this->x . ' ' . $this->y . ' ' . $this->w . ' ' . $this->h . "]";
	}
	
	function aspectRatio ()
	{
		return (float)$this->w / (float)$this->h;
	}
	
	function html($color='black', $title='', $inflate=10, $n = 0)
	{
		global $offset_y; // for debugging so we can move this somewhere we can see it....
		echo '<div style="position: absolute;left:' . ($this->x * $inflate) . ';top:' . ($this->y * $inflate + $offset_y)
			. ';width:' . ($this->w * $inflate) . ';height:' . ($this->h * $inflate) . ';border:1px solid ' . $color . ';">';
		echo '</div>';
	}
	
	
}


define (INDEX_BY_MIDDLE, 1);
define (EXPECTED_WASTE_FACTOR, 1.15);

//--------------------------------------------------------------------------------------------------
/**
 * @brief Encapsulate a rectangle
 *
 */
class QuantumTreemap
{
	var $origSizes = array();
	var $origBox;
	var $origiar;
	var $indexType;
	var $numQuadLayouts;
	var $numSnake3Layouts;
	var $numSnake4Layouts;
	var $numSnake5Layouts;
	var $resultRects = array();
	
	
	//----------------------------------------------------------------------------------------------	
	function __construct($sizes, $iar, $box)
	{
		$this->origSizes 	= $sizes;
		$this->origiar 		= $iar;
		$this->origBox 		= $box;
		$this->indexType 	= INDEX_BY_MIDDLE;
	}
	
	//----------------------------------------------------------------------------------------------	
	function export2json()
	{
		$obj = new stdclass;
		$obj->sizes = $this->origSizes;
		$obj->rects = $this->resultRects;
		
		return json_encode($obj);
	}
	
	//----------------------------------------------------------------------------------------------	
	function quantumLayout()
	{
		$this->numQuadLayouts   = 0;
		$this->numSnake3Layouts = 0;
		$this->numSnake4Layouts = 0;
		$this->numSnake5Layouts = 0;
	
		$area = $this->computeSize($this->origSizes);
		$area *= EXPECTED_WASTE_FACTOR;   // Add room for expected waste factor
		$ar = $this->origBox->aspectRatio();
		$h = (int)ceil(sqrt($area / $ar));
		$w = (int)ceil($area / $h);

		//echo "Aspect ratio=$ar, area=$area, w=$w, h=$h \n";
		
		$box = new Rectangle($this->origBox->x, $this->origBox->y, $this->origBox->x + $w, $this->origBox->y + $h);
	
		$boxAR = $box->aspectRatio();
		$growWide = (($boxAR >= 1) ? true : false);
		
		//echo ", growWide=" . ($growWide ? "true" : "false") . "\n";
	
		$this->resultRects = $this->quantumLayoutParams($this->origSizes, $box, $growWide);
	}
	
	//----------------------------------------------------------------------------------------------	
	function quantumLayoutParams(&$sizes, &$box, &$growWide)
	{
		global $debug;
		global $offset_y;
		
		if ($debug)
		{
			echo '<ul>';
			echo '<li>';
			$offset_y += 100;
		}
		
		$boxes = array();
		
		$pivotIndex = $this->computePivotIndex($sizes);
		$pivotSize = $sizes[$pivotIndex];
		$boxAR = $box->aspectRatio();
		
		//echo "pivotIndex=$pivotIndex";
		//echo '<br/>';

		
		if (count($sizes) == 1)
		{
			$boxes[] = $box;
			
			if ($debug)
			{
				echo "Stop 1: box = \n";
				$box->Dump();
				echo '<br/>';
			}
		}

		if (count($sizes) == 2)
		{
			$ratio = $sizes[0] / ($sizes[0] + $sizes[1]);
			if ($growWide)
			{
				$dim1 = $this->computeTableLayout($sizes[0], $boxAR * $ratio);
				$dim2 = $this->computeTableLayout($sizes[1], $boxAR * $ratio);							
				$h = max($dim1[1], $dim2[1]);
				
				//echo "h=$h<br/>";
				$dim2 = $this->computeTableLayoutGivenHeight($sizes[1], $h);
				$boxes[0] = new Rectangle($box->x, $box->y, $dim1[0], $h);
				$boxes[1] = new Rectangle($box->x + $dim1[0], $box->y , $dim2[0], $dim2[1]);
			}
			else
			{
				$dim1 = $this->computeTableLayout($sizes[0], $boxAR / $ratio);
				$dim2 = $this->computeTableLayout($sizes[1], $boxAR / (1 - $ratio));
				$w = max($dim1[0], $dim2[0]);
				
				//echo "w=$w<br/>";
				
				$dim2 = $this->computeTableLayoutGivenWidth($sizes[1], $w);
				$boxes[0] = new Rectangle($box->x, $box->y, $w, $dim1[1]);
				$boxes[1] = new Rectangle($box->x, $box->y + $dim1[1], $dim2[0], $dim2[1]);
			}
			
			if ($debug)
			{
				echo "Stop 2: box[0] = ";
				$boxes[0]->Dump();	
				echo '<br />';
				echo " Stop 2: box[1] = ";
				$boxes[1]->Dump();	
				echo '<br/>';
				echo 'Return ' . __LINE__ . '';
				echo '<br/>';
			}
			
			return $boxes;
		}
		
		// More than 2 
		$box2 = NULL;
		$r1 = NULL;
		$l1 = array();
		$l2 = array();
		$l3 = array();
				
		// First compute R1
		if ($pivotIndex > 0)
		{
			$l1 = array_slice($sizes, 0, $pivotIndex);
			$l1Size = $this->computeSize($l1);
			$b2Size = $this->computeSizeBetween($sizes, $pivotIndex, count($sizes) - 1);
			
			if ($growWide)
			{
				$dim1 = $this->computeTableLayoutGivenHeight($l1Size, $box->h);
				$dim2 = $this->computeTableLayoutGivenHeight($b2Size, $box->h);
				$r1 = new Rectangle($box->x, $box->y, $dim1[0], $dim1[1]);
				$box2 = new Rectangle($box->x + $dim1[0], $box->y, $dim2[0], $dim2[1]);
			}
			else
			{
				$dim1 = $this->computeTableLayoutGivenWidth($l1Size, $box->w);
				$dim2 = $this->computeTableLayoutGivenWidth($b2Size, $box->w);
				$r1 = new Rectangle($box->x, $box->y, $dim1[0], $dim1[1]);
				$box2 = new Rectangle($box->x, $box->y + $dim1[1], $dim2[0], $dim2[1]);			
			}
		}
		else
		{
			$box2 = new Rectangle($box->x, $box->y, $box->w, $box->h);
		}
				
		// Recurse on R1 to compute better box2
		
		if ($debug) { echo "<b>Recurse on R1 to get better box2</b><br />"; }
		
		if (count($l1) != 0)
		{
			if (count($l1) > 1)
			{
				$r1AR = $r1->aspectRatio();
				if ($r1AR == 1)
				{
					$newGrowWidth = $growWide;
				}
				else
				{
					$newGrowWidth = (($r1AR >= 1) ? true : false);
				}
				$l1boxes = $this->quantumLayoutParams($l1, $r1, $newGrowWide);
			}
			else
			{
				$l1boxes[0] = $r1;
			}
			
			$l1FinalBox = $this->computeUnion($l1boxes);
			if ($growWide)
			{
				$box2->h = $r1->h;
			}
			else
			{
				$box2->w = $r1->w;
			}
			/*
			echo "Final R1 box=";
			$l1FinalBox->Dump();
			echo '<br />';
			echo "box2=";
			$box2->Dump();
			echo '<br />';
			*/
		}
		
		// Display
		if ($debug) 
		{
			$box->html();
			$l1FinalBox->html('green', 'l1');
			$box2->html();
		}
		
		// Then compute R2 and R3
		$box2AR = $box2->aspectRatio();
		$first = true;
		$bestAR = 0.0;
		$bestdim1 = array();
		$bestIndex=0;
		for ($i = $pivotIndex+1; $i < count($sizes); $i++)
		{
			$l2Size = $this->computeSizeBetween($sizes, $pivotIndex+1, $i);
			$ratio = $pivotSize/($pivotSize + $l2Size);
			
			if ($growWide)
			{
				$h1 = (int)ceil($ratio * $box2->h);
				$dim1 = $this->computeTableLayoutGivenHeight($pivotSize, $h1);
			}
			else
			{
				$w1 = (int)ceil($ratio * $box2->w);
				$dim1 = $this->computeTableLayoutGivenWidth($pivotSize, $w1);
			}
			
			$pivotAR = max( $dim1[0]/$dim1[1] , $dim1[1]/$dim1[0]);
						
			if ($first || ($pivotAR < $bestAR)) 
			{
				$first = false;
				$bestAR = $pivotAR;
				$bestdim1 = $dim1;
				$bestl2Size = $l2Size;
				$bestIndex = $i;
			}
		}
		//echo "Best split: pivot=$pivotIndex, bestIndex=$bestIndex, bestAR=$bestAR, bestdim1=[" . $bestdim1[0] . ',' . $bestdim1[1] . "]<br />";
		
		$l2 = array();
		if ($bestIndex > 0)
		{
			$l2 = array_slice($sizes, $pivotIndex+1, $bestIndex - $pivotIndex);
			
			$nl3 = count($sizes) - 1 - $bestIndex;
			if (($nl3) > 0)
			{
				$l3 = array_slice($sizes, $bestIndex+1, $nl3);
			}
		}
/*		echo "\nSplit:\n";
		echo "l1\n";
		print_r($l1);
		echo "l2\n";
		print_r($l2);
		echo "l3\n";
		print_r($l3);*/
	
				
		if (count($l2) > 0)
		{
			if ($growWide)
			{
				$dim2 = $this->computeTableLayoutGivenHeight($bestl2Size, $box2->h - $bestdim1[1]);
				$rp = new Rectangle($box2->x, $box2->y, $bestdim1[0], $bestdim1[1]);
				$r2 = new Rectangle($box2->x, $box2->y + $dim1[1], $dim2[0], $dim2[1]);
				
				if (count($l3) > 0)
				{
					$l3size = $this->computeSizeBetween($sizes, $bestIndex+1, count($sizes) - 1);
					$dim3 = $this->computeTableLayoutGivenHeight($l3size, $box2->h);
					$r3 = new Rectangle($box2->x + $dim2[0], $box2->y, $dim3[0], $dim3[1]);
				}
			}
			else
			{
				$dim2 = $this->computeTableLayoutGivenWidth($bestl2Size, $box2->w - $bestdim1[0]);
				$rp = new Rectangle($box2->x, $box2->y, $bestdim1[0], $bestdim1[1]);
				$r2 = new Rectangle($box2->x + $dim1[0], $box2->y, $dim2[0], $dim2[1]);
				
				if (count($l3) > 0)
				{
					$l3size = $this->computeSizeBetween($sizes, $bestIndex+1, count($sizes) - 1);
					$dim3 = $this->computeTableLayoutGivenWidth($l3size, $box2->w);
					$r3 = new Rectangle($box2->x, $box2->y + $dim2[1], $dim3[0], $dim3[1]);
				}
			}
		}
		else
		{
			if ($growWide)
			{
				$dim1 = $this->computeTableLayoutGivenHeight($pivotSize, $r1->h);
			}
			else
			{
				$dim1 = $this->computeTableLayoutGivenWidth($pivotSize, $r1->w);
			}		
			$rp = new Rectangle($box2->x, $box2->y, $dim1[0], $dim1[1]);
		}
		
		/*echo "dim1\n";print_r($dim1);
		echo "dim2\n";print_r($dim2);
		echo "dim3\n";print_r($dim3);*/
		
		//echo "rp = ";$rp->Dump(); echo "<br/>";
		//echo "r2 = ";$r2->Dump(); echo "<br/>";
		/*
		if ($r3)
		{
			echo "r3";$r3->Dump(); echo "<br/>";
		}
		
		*/
		
		if ($debug)
		{
			echo "Draw<br/>";
			$offset_y += 100;
			$rp->html("red", 'rp');
			$r2->html("orange", 'r2');
			if ($r3)
			{
				$r3->html("blue", 'r3');
			}
			echo "r2=";
			$r2->Dump();
			echo "<br/>";
		}	
		
		//------------------------------------------------------------------------------------------
		// Finally, recurse on sublists in R2 and R3
		if ($debug) { echo '<b>Recurse on sublists in R2 and R3</b><br />'; }
		if (count($l2) != 0)
		{
			if (count($l2) > 1)
			{
				if ($debug) { echo "recurse on R2<br/>"; }
				$r2AR = $r2->aspectRatio();

				if ($debug) { echo "r2AR=$r2AR<br/>"; }
				
				if ($r2AR == 1)
				{
					$newGrowWide = $growWide;
				}
				else
				{
					$newGrowWide = (($r2AR >= 1) ? true : false);
				}
				$l2boxes = $this->quantumLayoutParams($l2, $r2, $newGrowWide);
			}
			else
			{
				$l2boxes[0] = $r2;
			}
			$l2FinalBox = $this->computeUnion($l2boxes);
			
			if ($debug) { echo "Final R2 box="; $l2FinalBox->Dump(); echo "<br/>"; }
		}

		if (count($l3) != 0)
		{
			if (count($l3) > 1)
			{
				if ($debug) { echo "<b>Recurse on R3</b><br />"; }
				$r3AR = $r3->aspectRatio();
				if ($r3AR == 1)
				{
					$newGrowWide = $growWide;
				}
				else
				{
					$newGrowWide = (($r3AR >= 1) ? true : false);
				}
				$l3boxes = $this->quantumLayoutParams($l3, $r3, $newGrowWide);
			}
			else
			{
				$l3boxes[0] = $r3;
			}
			$l3FinalBox = $this->computeUnion($l3boxes);
			
			if ($debug) { echo "Final R3 box="; $l3FinalBox->Dump(); echo "<br/>"; }
		}
		
		
					
		$rp_array = array();			
		$r3_array = array(); // empty
		$rp_array[] = $rp;
		
		/*echo "rp = "; $rp_array[0]->Dump(); echo "<br />";
		echo "l1 = "; $l1FinalBox->Dump(); echo "<br />";
		*/
		
		if ($debug) { echo '<b>Shift, expand/contract</b><br />'; }
		
		if (1)
		{
					
		// Shift and expand/contract the new layouts
		// depending on the the other sub-layouts
		if ($growWide)
		{
			if ($debug) { echo '<b>gw</b><br/>'; }
			if (count($l1) > 0)
			{
				$rp_array[0]->x = $l1FinalBox->x + $l1FinalBox->w;
				$rp_array[0]->y = $l1FinalBox->y;
				
				if ($debug) { echo "rp = "; $rp_array[0]->Dump(); echo "<br />"; }
			}
			if (count($l2) > 0)
			{
				if ($debug) { echo __LINE__. '<br/>'; }
				
				$this->translateBoxesTo($l2boxes, $rp_array[0]->x, $rp_array[0]->y + $rp_array[0]->h);

				if ($debug) 
				{ 
					foreach ($l2boxes as $l)
					{
						echo "l = "; $l->Dump(); echo "<br />"; 
					}
				}

				$this->evenBoxWidth($rp_array, $l2boxes, $r3_array);
				
				
				if ($debug) 
				{ 
					foreach ($l2boxes as $l)
					{
						echo "l = "; $l->Dump(); echo "<br />"; 
					}
				}
				//exit();
				
				
				if (count($l3) > 0)
				{
					$l2FinalBox = $this->computeUnion($l2boxes);
					if ($debug) { echo l2FinalBox; $l2FinalBox->Dump(); echo "<br />"; }
					if ($debug) { echo __LINE__. '<br/>'; }
						
					$this->translateBoxesTo($l3boxes, $l2FinalBox->x + $l2FinalBox->w, $rp_array[0]->y);
				}
				$this->evenBoxHeight($l1boxes, $l2boxes, $l3boxes);
			}
			else
			{
				$this->evenBoxHeight($rp_array, $l1boxes, $r3_array);
			}
		} 
		else
		{
			if ($debug) { echo '<b>no gw</b><br/>'; }
			if (count($l1) > 0)
			{
				$rp_array[0]->x = $l1FinalBox->x;
				$rp_array[0]->y = $l1FinalBox->y + $l1FinalBox->h;
				if ($debug) { echo "rp = "; $rp_array[0]->Dump(); echo "<br />"; }
			}
			if (count($l2) > 0)
			{
				//echo __LINE__. '<br/>';
				//print_r($l2boxes);
				$this->translateBoxesTo($l2boxes, $rp_array[0]->x + $rp_array[0]->w, $rp_array[0]->y);
				if ($debug) 
				{ 
					foreach ($l2boxes as $l)
					{
						echo "l = "; $l->Dump(); echo "<br />"; 
					}
				}
				$this->evenBoxHeight($rp_array, $l2boxes, $r3_array);
				//print_r($rp_array);
				//print_r($l2boxes);
				if (count($l3) > 0)
				{
					$l2FinalBox = $this->computeUnion($l2boxes);
					if ($debug) { echo l2FinalBox; $l2FinalBox->Dump(); echo "<br />"; }
					if ($debug) { echo __LINE__. '<br/>'; }
					$this->translateBoxesTo($l3boxes, $rp_array[0]->x, $l2FinalBox->y + $l2FinalBox->h);
				}
				
				$this->evenBoxWidth($l1boxes, $l2boxes, $l3boxes);
			}
			else
			{
				$this->evenBoxWidth($rp_array, $l1boxes, $r3_array);
			}
		}
		
		}
		
			
		if (count($l1) > 0)
		{
			$boxes = array_merge($boxes, $l1boxes);
		}
		
		$boxes[] = $rp_array[0];
		
		if (count($l2) > 0)
		{
			$boxes = array_merge($boxes, $l2boxes);
		}
		if (count($l3) > 0)
		{
			$boxes = array_merge($boxes, $l3boxes);
		}
		
		$boxAR = $box->aspectRatio();
		if ($boxAR == 1)
		{
			$newGrowWide = $growWide;
		}
		else
		{
			$newGrowWide = (($boxAR >= 1) ? true : false);
		}

		if ($debug)
		{
			echo '<b>Boxes</b><br />';
			foreach ($boxes as $b)
			{
				$b->Dump();
				echo "<br/>";
			}
			
			echo 'Return ' . __LINE__ . '';
		}
		
		if ($debug)
		{
			echo '</li>';
			echo '</ul>';
		}


		return $boxes;	
	}
	
	
	//----------------------------------------------------------------------------------------------	
	function translateBoxesTo ($boxes, $x, $y)
	{
		$box = $this->computeUnion($boxes);
		$dx = $x - $box->x;
		$dy = $y - $box->y;
		for ($i = 0; $i < count($boxes); $i++)
		{
			$boxes[$i]->x += $dx;
			$boxes[$i]->y += $dy;
		}
	}			
		
	
	
	//----------------------------------------------------------------------------------------------	
	function computeUnion($boxes)
	{
		$x1 = $y1 = $x2 = $y2 = 0;
		
		$box = new Rectangle($boxes[0]->x, $boxes[0]->y, $boxes[0]->w, $boxes[0]->h);
		
		for ($i = 1; $i < count($boxes); $i++)
		{
			$x1 = min($box->x, $boxes[$i]->x);
			$x2 = max($box->x + $box->w, $boxes[$i]->x + $boxes[$i]->w);
			$y1 = min($box->y, $boxes[$i]->y);
			$y2 = max($box->y + $box->h, $boxes[$i]->y + $boxes[$i]->h);
			
			$box->x = $x1;
			$box->y = $y1;
			$box->w = $x2 - $x1;
			$box->h = $y2 - $y1;
		}
		return $box;
    }
	
	
	//----------------------------------------------------------------------------------------------	
	function computeTableLayout ($numItems, $ar)
	{	
		$h = 0;
		$w = 0;
		
		if ($ar >= 1)
		{
			$h = ceil(sqrt($numItems/$ar));
			if ($h == 0) { $h = 1; }
			$w = round($numItems/$h);
			if (($w * $h) < $numItems)
			{
				$w++;
				$h--;
			}
			while (($w * $h) < $numItems) { $h++; };
		}
		else
		{
			$w = ceil(sqrt($numItems * $ar));
			if ($w == 0) { $w = 1; }
			$h = round($numItems/$w);
			if (($w * $h) < $numItems)
			{
				$h++;
				$w--;
			}
			while (($w * $h) < $numItems) { $w++; };
		}		
		
		return array($w, $h);
	}
	
	//----------------------------------------------------------------------------------------------	
	function computeTableLayoutGivenHeight($numItems, $height) 
	{
		if ($height < 1) { $height = 1; }
		$w = (int)ceil($numItems / $height);
		return array($w, $height);
    }

	//----------------------------------------------------------------------------------------------	
	function computeTableLayoutGivenWidth($numItems, $width) 
	{
		if ($width < 1) { $width = 1; }
		$h = (int)ceil($numItems / $width);
		return array($width, $h);
    }
	
	
	//----------------------------------------------------------------------------------------------	
	function computePivotIndex ($sizes)
	{
		$index = 0;
		$first = true;
		$bestRatio = 0;
		
		switch ($this->indexType)
		{
			case INDEX_BY_MIDDLE:
				$index = floor((count($sizes) - 1) / 2);
				break;
				
			default:
				break;
		}
		return $index;
	}
				
	
	//----------------------------------------------------------------------------------------------	
	function computeSize($arr)
	{
		$size = 0;
		foreach ($arr as $s) {$size += $s; }
		return $size;
	}

	//----------------------------------------------------------------------------------------------	
   /**
     * Compute the total size of the objects between the specified indices, inclusive.
     */
	function computeSizeBetween($sizes, $i1, $i2)
	{
		$size = 0;
		for ($i = $i1; $i <= $i2; $i++) { $size += $sizes[$i]; }
		return $size;
	}
	
	
	function evenBoxHeight (&$b1, &$b2, &$b3)
	{
		$b1bounds = NULL;
		$b2bounds = NULL;
		$b3bounds = NULL;
		
		// Compute the actual bounds of the 3 regions
		if (count($b1) != 0)
		{
			$b1bounds = $this->computeUnion($b1);
		}
		else
		{
			$b1Bounds = new Rectangle(0,0,0,0);
		}
		if (count($b2) != 0)
		{
			$b2bounds = $this->computeUnion($b2);
		}
		else
		{
			$b2Bounds = new Rectangle(0,0,0,0);
		}
		if (count($b3) != 0)
		{
			$b3bounds = $this->computeUnion($b3);
		}
		else
		{
			$b3Bounds = new Rectangle(0,0,0,0);
		}
	
		// Then, compute the preferred new height which is
		// the max of all the heights;
		$newBottom = max(max(($b1bounds->y + $b1bounds->h), ($b2bounds->y + $b2bounds->h)),
						($b3bounds->y + $b3bounds->h));
						
		// Then, fix up each region that is not the same height
		if (count($b1) != 0) 
		{
			if (($b1bounds->y + $b1bounds->h) != $newBottom)
			{
				$dy = $newBottom - ($b1bounds->y + $b1bounds->h);
				$bottom = $b1bounds->y + $b1bounds->h;
				for ($i = 0; $i < count($b1); $i++)
				{
					if (($b1[$i]->y + $b1[$i]->h) == $bottom)
					{
						$b1[$i]->h += $dy;
					}
				}
			}
		}
						
		if (count($b2) != 0) 
		{
			if (($b2bounds->y + $b2bounds->h) != $newBottom)
			{
				$dy = $newBottom - ($b2bounds->y + $b2bounds->h);
				$bottom = $b2bounds->y + $b2bounds->h;
				for ($i = 0; $i < count($b2); $i++)
				{
					if (($b2[$i]->y + $b2[$i]->h) == $bottom)
					{
						$b2[$i]->h += $dy;
					}
				}
			}
		}
		
		if (count($b3) != 0) 
		{
			if (($b3bounds->y + $b3bounds->h) != $newBottom)
			{
				$dy = $newBottom - ($b3bounds->y + $b3bounds->h);
				$bottom = $b3bounds->y + $b3bounds->h;
				for ($i = 0; $i < count($b3); $i++)
				{
					if (($b3[$i]->y + $b3[$i]->h) == $bottom)
					{
						$b3[$i]->h += $dy;
					}
				}
			}
		}	
		
	}
	
	//----------------------------------------------------------------------------------------------
	function evenBoxWidth (&$b1, &$b2, &$b3)
	{
		$b1bounds = NULL;
		$b2bounds = NULL;
		$b3bounds = NULL;
		
		// Compute the actual bounds of the 3 regions
		if (count($b1) != 0)
		{
			$b1bounds = $this->computeUnion($b1);
		}
		else
		{
			$b1Bounds = new Rectangle(0,0,0,0);
		}
		if (count($b2) != 0)
		{
			$b2bounds = $this->computeUnion($b2);
		}
		else
		{
			$b2Bounds = new Rectangle(0,0,0,0);
		}
		if (count($b3) != 0)
		{
			$b3bounds = $this->computeUnion($b3);
		}
		else
		{
			$b3Bounds = new Rectangle(0,0,0,0);
		}
	
		// First compute the preferred new width which is
		// the max of all the widths;	
		$newRight = max(max(($b1bounds->x + $b1bounds->w), ($b2bounds->x + $b2bounds->w)),
						($b3bounds->x + $b3bounds->w));
						
		// Then, fix up each region that is not the same width
		if (count($b1) != 0) 
		{
			if (($b1bounds->x + $b1bounds->w) != $newRight)
			{
				$dx = $newRight - ($b1bounds->x + $b1bounds->w);
				$right = $b1bounds->x + $b1bounds->w;
				for ($i = 0; $i < count($b1); $i++)
				{
					if (($b1[$i]->x + $b1[$i]->w) == $right)
					{
						$b1[$i]->w += $dx;
					}
				}
			}
		}
						
		if (count($b1) != 0) 
		{
			if (($b1bounds->x + $b1bounds->w) != $newRight)
			{
				$dx = $newRight - ($b1bounds->x + $b1bounds->w);
				$right = $b1bounds->x + $b1bounds->w;
				for ($i = 0; $i < count($b1); $i++)
				{
					if (($b1[$i]->x + $b1[$i]->w) == $right)
					{
						$b1[$i]->w += $dx;
					}
				}
			}
		}
		
		if (count($b2) != 0) 
		{
			if (($b2bounds->x + $b2bounds->w) != $newRight)
			{
				$dx = $newRight - ($b2bounds->x + $b2bounds->w);
				$right = $b2bounds->x + $b2bounds->w;
				for ($i = 0; $i < count($b2); $i++)
				{
					if (($b2[$i]->x + $b2[$i]->w) == $right)
					{
						$b2[$i]->w += $dx;
					}
				}
			}
		}	
		
		if (count($b3) != 0) 
		{
			if (($b3bounds->x + $b3bounds->w) != $newRight)
			{
				$dx = $newRight - ($b3bounds->x + $b3bounds->w);
				$right = $b3bounds->x + $b3bounds->w;
				for ($i = 0; $i < count($b3); $i++)
				{
					if (($b3[$i]->x + $b3[$i]->w) == $right)
					{
						$b3[$i]->w += $dx;
					}
				}
			}
		}	
		
	}
}


//--------------------------------------------------------------------------------------------------
// http://www.herethere.net/~samson/php/color_gradient/
// Return the interpolated value between pBegin and pEnd
function interpolate($pBegin, $pEnd, $pStep, $pMax) 
{
	if ($pBegin < $pEnd) 
	{
  		return (($pEnd - $pBegin) * ($pStep / $pMax)) + $pBegin;
	} 
	else 
	{
  		return (($pBegin - $pEnd) * (1 - ($pStep / $pMax))) + $pEnd;
	}
}

//--------------------------------------------------------------------------------------------------
function draw($j)
{
	global $config;


	$quantum = 75;
	$spacing = 10;
	$half_spacing = $spacing / 2;
	$aspect_ratio = 0.8;
	
	$cell_w = $quantum * $aspect_ratio + $spacing;
	$cell_h = $quantum  + $spacing;

	echo '<div style="top:' . $half_spacing . 'px;left:' . $half_spacing . 'px;position:absolute;">';
	
	// Use a colour gradient to colour cells
	$theColorBegin = 0xdddddd;
	$theColorEnd = 0x000000;
	
	$theR0 = ($theColorBegin & 0xff0000) >> 16;
	$theG0 = ($theColorBegin & 0x00ff00) >> 8;
	$theB0 = ($theColorBegin & 0x0000ff) >> 0;
	
	$theR1 = ($theColorEnd & 0xff0000) >> 16;
	$theG1 = ($theColorEnd & 0x00ff00) >> 8;
	$theB1 = ($theColorEnd & 0x0000ff) >> 0;
	
	$n = count($j->sizes);
	for ($i = 0; $i < $n; $i++)
	{
		$num_rows = ceil($j->sizes[$i] / $j->rects[$i]->w);
		
		// Background colour
		$theR = interpolate($theR0, $theR1, $i, $n);
		$theG = interpolate($theG0, $theG1, $i, $n);
		$theB = interpolate($theB0, $theB1, $i, $n);
		$theVal = ((($theR << 8) | $theG) << 8) | $theB;
		
		$colour = sprintf("background-color: #%06X; ", $theVal);
	
		// Cell holding quanta
		echo '<div style="position:absolute;'
			//. 'border:1px solid black;'
			. 'left:' . ($j->rects[$i]->x * $cell_w) . 'px;'
			. 'top:' . ($j->rects[$i]->y * $cell_h) . 'px;'
			. 'width:' . ($j->rects[$i]->w * $cell_w) . 'px;'
			. 'height:' . ($j->rects[$i]->h * $cell_h) . 'px;'
			
			// Webkit (but see http://blogs.sitepoint.com/2011/01/18/webkit-updates-css3-gradient-support-matches-mozilla-syntax/ )
			. 'background: -webkit-gradient(linear, left top, right bottom, from(#CCDEFD), to(#FFFFFF));'
			
			// Firefox
			. 'background: -moz-linear-gradient(-45deg, #aaa, #fff);'	
			
			// IE
			. 'filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr=\'#aaaaaa\', EndColorStr=\'#ffffff\');'
			. '">';
			
		// Draw individual quanta
		$k = 0;
		$row = 0;
		$col = 0;
		while ($k < $j->sizes[$i])
		{
			echo '<div style="position:absolute;'
			. 'left:' . ($col * $cell_w + $half_spacing) . 'px;'
			. 'top:' . ($row * $cell_h + $half_spacing) . 'px;'
			. 'width:' . $quantum * $aspect_ratio . 'px;'
			. 'height:' . $quantum . 'px;'
			. 'overflow:hidden;'
			. 'line-height:' . $quantum . 'px;' // vertically centre text
			. 'text-align:center;'
			. 'font-family:Arial,Verdana,sans-serif;font-size:10px;'
			. '"'
			.'>';
			
			
			if (isset($j->rects[$i]->ids))
			{
				echo '<a href="id/' . $j->rects[$i]->ids[$k] . '">';
				echo '<img style="vertical-align:middle;" src="' . $config['web_server'] . $config['web_root'] . 'thumbnail.php?id=' . $j->rects[$i]->ids[$k] . '" height="' . $cell_h . '" border="0" />';
				echo '</a>';
			}			
			
			echo '</div>';
			
			
			$col++;
			if ($col == $j->rects[$i]->w)
			{
				$row++;
				$col = 0;
			}
			$k++;
		}
		
		// see http://stackoverflow.com/questions/3793204/click-through-div-with-an-alpha-channel
		// pointer-events:none;
		echo '<div style="pointer-events:none;position:absolute;z-index:10;font-size:16px;font-weight:bold;font-family:Arial;overflow:hidden;text-overflow: ellipsis;opacity:0.6;'
		. 'width:' . ($j->rects[$i]->w * $cell_w) . 'px;'
		. '">' . $j->rects[$i]->label . '</div>';
		
		
		echo '</div>';
	
	}
	
	echo '</div>';

}



?>