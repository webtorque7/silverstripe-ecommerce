<?php

class CleanupProductFullSiteTreeSorting extends BuildTask{

	protected $title = "Cleanup Product Full SiteTree Sorting";

	protected $description = "resets all the sorting values in the Full Site Tree Sorting field.  This field includes the sorting number for the product at hand, as well as all the sorting number of its parent pages... Allowing you to keep the SiteTree sort order for a collection of random products. ";

	protected $deleteFirst = true;

	function setDeleteFirst($b){
		$this->deleteFirst = $b;
	}

	function run($request){
		$stagingArray = array("_Live", "");
		foreach($stagingArray as $extension) {
			DB::alteration_message("updating staging: $extension");
			if($this->deleteFirst) {
				DB::query("UPDATE Product$extension SET \"FullSiteTreeSort\" = '';");
			}
			for($i = 30; $i > 0; $i--) {
				$joinStatement = "
					INNER JOIN SiteTree$extension AS UP0 ON UP0.ID = Product$extension.ID";
				$concatStatement = "CONCAT(";
				for($j = 1; $j < $i; $j++) {
					$concatStatement .= "UP".($i-$j).".Sort,',',";
					$joinStatement .= "
						INNER JOIN SiteTree$extension AS UP$j ON UP$j.ID = UP".($j-1).".ParentID";
				}
				$concatStatement .= "UP0.Sort)";
				$sql = "
					SELECT COUNT(\"Product$extension\".\"ID\")
					FROM  \"Product$extension\"
					$joinStatement
					WHERE \"Product$extension\".\"FullSiteTreeSort\" IS NULL OR \"Product$extension\".\"FullSiteTreeSort\" = '';
				";
				$count = DB::query($sql)->value();
				if($count) {
					DB::alteration_message("We are about to update $count Products", "created");
					$sql = "
						UPDATE \"Product$extension\"
						$joinStatement
						SET \"Product$extension\".\"FullSiteTreeSort\" = $concatStatement
						WHERE \"Product$extension\".\"FullSiteTreeSort\" IS NULL OR \"Product$extension\".\"FullSiteTreeSort\" = '';";
					DB::query($sql);
					$outcome = DB::query($sql);
					echo "<p style=\"font-size: 10px; color: grey;\">".$sql."</p>";
				}
			}
		}
		$missedOnes = DataObject::get("Product", "\"FullSiteTreeSort\" IS NULL OR \"FullSiteTreeSort\" = ''");
		if($missedOnes) {
			DB::alteration_message("ERROR: could not updated all Product.FullSiteTreeSort numbers!", "deleted");
		}
		else {
			DB::alteration_message("All Product.FullSiteTreeSort have been updated");
		}
		$examples = DataObject::get("Product", "", "RAND()", null, 3);
		foreach($examples as $key => $example) {
			DB::alteration_message("EXAMPLE #$key: ".$example->Title.": <strong>".$example->FullSiteTreeSort."</strong>");
		}
	}

}