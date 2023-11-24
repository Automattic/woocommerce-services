<?php
function version_compare_reverse($i, $j) {
	 return version_compare($i, $j) * -1;
}

/**
 * Take input from stdin and adds all valid (ie. x.y.z) versinos to an array.
 */
function filter_valid_versions() {
	$versions = [];
	$fp = fopen("php://stdin", 'r');
	$contents = fgets($fp);
	while ($contents !== false) {
		$contents = fgets($fp);
		if (!preg_match('/^\d+\.\d+\.\d+$/', $contents)) {
			continue;
		}
		$versions[] = trim($contents);
	}
	return $versions;
}

/**
 * Take an array of $versions and return the latest 3 minor versions.
 *
 * @param Array $versions The array need to be sorted first.
 * @return 3 minor versions, in the order of oldest to newest.
 */
function get_last_three_minor($versions) {
	$last_minor_version = '';
	$results = [];
	foreach ($versions as $index => $version_number) {
		$current_minor_version = substr($version_number, 0, strrpos($version_number, '.'));

		if (count($results) >= 2) {
			break;
		}

		if ($last_minor_version === $current_minor_version) {
			continue;
		}

		$last_minor_version = $current_minor_version;
		$results[] = "'$version_number'";
	}

	return array_reverse($results);
}

// Output 3 latest minor versions in comma separated values, wrapped in [] for github action matrix
$versions = filter_valid_versions();
usort($versions, 'version_compare_reverse');
$results = get_last_three_minor($versions);
echo '[' . implode(",",$results) . ']';
