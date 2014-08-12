<?php
	
namespace Skolar\Browser;

class PageStorage implements \ArrayAccess, \Countable, \JsonSerializable {
	private $storage = array();

	public function get($query, $all = false) {
		foreach($this->storage as $page) {

			$urls = $page->getUrls();

			$combinations = array(
				$urls["end"],
				$urls["base"].$urls["end"]
			);

			if($all) {
				$combinations = array_merge($combinations, array($urls["begin"], $urls["base"].$urls["begin"]));
			}

			if(in_array($query, $combinations)) {
				return $page; 
			}
		}

		return false;
	}

	public function offsetSet($offset, $value) {
		if(is_null($offset)) {
			$this->storage[] = $value;
		} else {
			$this->storage[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return isset($this->storage[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->storage[$offset]);
	}

	public function offsetGet($offset) {
		return (isset($this->storage[$offset])) ? $this->storage[$offset] : null;
	}
	
	public function count() {
		return count($this->storage);
	}

	public function jsonSerialize() {
		return $this->storage;
	}
}
	
?>