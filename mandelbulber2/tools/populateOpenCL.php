#!/usr/bin/env php
#
# this file autogenerates misc files from cpp to opencl
#
# requires clang-format and php (apt-get install clang-format php5-cli)
# clang-format is required in version 3.8.1, get executable from here: http://releases.llvm.org/download.html
#
# on default this script runs dry,
# it will try to generate the needed files and show which files would be modified
# this should always be run first, to see if any issues occur
# if you invoke this script with "nondry" as cli argument it will write the changes
# to the opencl files
#

<?php
require_once(dirname(__FILE__) . '/common.inc.php');

printStart();

$copyFiles = array();
$copyFiles[] = array('src' => 'fractal.h', 'opencl' => 'fractal_cl.h');
$copyFiles[] = array('src' => 'fractparams.hpp', 'opencl' => 'fractparams_cl.hpp');
$copyFiles[] = array('src' => 'image_adjustments.h', 'opencl' => 'image_adjustments_cl.h');
$copyFiles[] = array('src' => 'common_params.hpp', 'opencl' => 'common_params_cl.hpp');
$copyFiles[] = array('src' => 'fractal_coloring.hpp', 'opencl' => 'fractal_coloring_cl.hpp');
$copyFiles[] = array('src' => 'texture_enums.hpp', 'opencl' => 'texture_enums_cl.h');
$copyFiles[] = array('src' => 'object_types.hpp', 'opencl' => 'object_type_cl.h');

printStartGroup('RUNNING OPENCL AUTOGENERATION');
foreach ($copyFiles as $type => $copyFile) {
	$copyFile['path'] = PROJECT_PATH . 'src/' . $copyFile['src'];
	$copyFile['pathTarget'] = PROJECT_PATH . 'opencl/' . $copyFile['opencl'];
	$status = array();
	$success = autogenOpenCLFile($copyFile, $status);
	printResultLine(basename($copyFile['pathTarget']), $success, $status);
}
printEndGroup();

printStartGroup('RUNNING OPENCL DEFINE COLLECT');
$status = array();
$targetDefineFile = PROJECT_PATH . 'opencl/defines_cl.h';
$success = defineCollect($targetDefineFile, $status);
printResultLine(basename($targetDefineFile), $success, $status);
printEndGroup();


printFinish();
exit;

function defineCollect($defineFile, &$status)
{
	// $oldContent = file_get_contents($defineFile);
	$status[] = warningString('TODO');
	return true;
}

function autogenOpenCLFile($copyFile, &$status)
{
	$oldContent = file_get_contents($copyFile['pathTarget']);
	$content = file_get_contents($copyFile['path']);

	// add the "autogen" - line to the file header
	$headerRegex = '/^(\/\*\*?[\s\S]*?\*\/)([\s\S]*)$/';
	if (!preg_match($headerRegex, $content, $matchHeader)) {
		$status[] = errorString('header unknown!');
		return false;
	}
	$fileHeader = $matchHeader[1];
	$fileSourceCode = $matchHeader[2];
	$noChangeComment = array();
	$noChangeComment[] = '      ____                   ________ ';
	$noChangeComment[] = '     / __ \____  ___  ____  / ____/ / ';
	$noChangeComment[] = '    / / / / __ \/ _ \/ __ \/ /   / /  ';
	$noChangeComment[] = '   / /_/ / /_/ /  __/ / / / /___/ /___';
	$noChangeComment[] = '   \____/ .___/\___/_/ /_/\____/_____/';
	$noChangeComment[] = '       /_/                            ';
	$noChangeComment[] = '';
	$noChangeComment[] = 'This file has been autogenerated by tools/populateOpenCL.php';
	$noChangeComment[] = 'from the file ' . str_replace(PROJECT_PATH, '', $copyFile['path']);
	$noChangeComment[] = 'D O    N O T    E D I T    T H I S    F I L E !';
	$fileHeader = str_replace('*/', '* ' . implode(PHP_EOL . ' * ', $noChangeComment) . PHP_EOL . ' */', $fileHeader);
	
	$content = $fileHeader . $fileSourceCode;

	// replace opencl specific tokens (and replace all matches)
	$openCLMatchAppendCL = array(
		array('find' => '/struct\s([a-zA-Z0-9_]+)\n/'),
		array('find' => '/enum\s([a-zA-Z0-9_]+)\n/'),
		array('find' => '/const int\s([a-zA-Z0-9_]+)\s=\s/'),
	);
	foreach ($openCLMatchAppendCL as $item) {
		preg_match_all($item['find'], $content, $match);
		if (!empty($match[1])) {
			foreach ($match[1] as $replace) {
				$content = preg_replace('/(' . $replace . ')([ \]\(;\n])/', '$1Cl$2', $content);
				$stripEnum = lcfirst(str_replace('enum', '', $replace));
				$content = preg_replace('/(' . $stripEnum . ')_([a-zA-Z0-9_]+)/', '$1Cl_$2', $content);
			}
		}
	}

	// replace opencl specific tokens
	$openCLReplaceLookup = array(
		array('find' => '/(\s)int(\s)/', 'replace' => '$1cl_int$2'),
		array('find' => '/(\s)bool(\s)/', 'replace' => '$1cl_int$2'),
		array('find' => '/(\s)double(\s)/', 'replace' => '$1cl_float$2'),
		array('find' => '/(\s)float(\s)/', 'replace' => '$1cl_float$2'),
		array('find' => '/(\s)sRGB(\s)/', 'replace' => '$1cl_float3$2'),
		array('find' => '/(\s)sRGBFloat(\s)/', 'replace' => '$1cl_float3$2'),
		array('find' => '/(\s)CVector3(\s)/', 'replace' => '$1cl_float3$2'),
		array('find' => '/(\s)CVector3/', 'replace' => '$1cl_float3'),
		array('find' => '/(\s)CVector4(\s)/', 'replace' => '$1cl_float4$2'),
		array('find' => '/(\s)[a-zA-Z0-9_]+::[a-zA-Z0-9_]+(\s)/', 'replace' => '$1cl_int$2'), // enums with outer scope (::) get cast to int

		array('find' => '/struct\s([a-zA-Z0-9_]+)\n(\s*)({[\S\s]+?\n\2})/', 'replace' => "typedef struct\n$2$3 $1"),
		array('find' => '/enum\s([a-zA-Z0-9_]+)\n(\s*)({[\S\s]+?\n\2})/', 'replace' => "typedef enum\n$2$3 $1"),
		array('find' => '/const cl_int\s([a-zA-Z0-9_]+)\s=\s([a-zA-Z0-9_]+);/', 'replace' => "#define $1 $2"),
		array('find' => '/\n#include\s.*/', 'replace' => ''), // remove includes
		array('find' => '/(\s)CRotationMatrix(\s)/', 'replace' => '$1matrix33$2'),
		array('find' => '/(\s)CRotationMatrix44(\s)/', 'replace' => '$1matrix44$2'),

		array('find' => '/class\s([a-zA-Z0-9_]+);/', 'replace' => ""), // remove forward declaration
		array('find' => '/struct\s([a-zA-Z0-9_]+);/', 'replace' => ""), // remove forward declaration

		array('find' => '/\/\/\sforward declarations/', 'replace' => ""), // remove comment "forward declaration"
		array('find' => '/MANDELBULBER2_SRC_(.*)_HPP_/', 'replace' => "MANDELBULBER2_OPENCL_$1_CL_HPP_"), // include guard 1
		array('find' => '/MANDELBULBER2_SRC_(.*)_H_/', 'replace' => "MANDELBULBER2_OPENCL_$1_CL_H_"), // include guard

		// TODO rework these regexes
		array('find' => '/using namespace[\s\S]*?\n}\n/', 'replace' => ""), // no namespace support -> TODO fix files with namespaces
		array('find' => '/\nnamespace[\s\S]*?{([\s\S]*?\n)};?(?:\s*\/\/.*)?\n/', 'replace' => "\n$1"), // no namespace support -> TODO fix files with namespaces
		array('find' => '/sParamRenderCl\([\s\S]*?\);/', 'replace' => ""), // remove constructor
		array('find' => '/sFractalCl\([\s\S]*?\);/', 'replace' => ""), // remove constructor
		array('find' => '/sImageAdjustmentsCl\([\s\S]*?}/', 'replace' => ""), // remove constructor
		array('find' => '/void RecalculateFractalParams\([\s\S]*?\);/', 'replace' => ""), // remove method
		array('find' => '/cl_float CalculateColorIndex\([\s\S]*?\);/', 'replace' => ""), // remove method

		array('find' => '/.*::.*/', 'replace' => ""), // no namespace scopes allowed?
		array('find' => '/.*cPrimitives.*/', 'replace' => ""), // need to include file...
		array('find' => '/sCommonParams /', 'replace' => "sCommonParamsCl "), // TODO autogen replace over all files
		array('find' => '/sImageAdjustments /', 'replace' => "sImageAdjustmentsCl "), // TODO autogen replace over all files

		array('find' => '/matrix44 /', 'replace' => "// matrix44 "), // TODO

		array('find' => "/ &([a-zA-Z0-9_]+)/", 'replace' => ' *$1'), // no passing by reference
		array('find' => "/const sExtendedAux \*extendedAux/", 'replace' => 'const sExtendedAuxCl *extendedAux'), // rewrite
		array('find' => "/const sFractal \*defaultFractal/", 'replace' => 'const sFractalCl *defaultFractal'), // rewrite
		array('find' => "/const sFractalColoring \*fractalColoring/", 'replace' => 'const sFractalColoringCl *fractalColoring'), // rewrite

		array('find' => "/extendedAux\./", 'replace' => 'extendedAux->'), // pointer dereference
		array('find' => "/fractalColoring\./", 'replace' => 'fractalColoring->'), // pointer dereference


		array('find' => '/([a-zA-Z0-9_]+\(\));/', 'replace' => ""), // remove constructor declaration
		array('find' => '/([a-zA-Z0-9_\[\]\s\-\+]+)(?:{.*})?(;.*)/', 'replace' => "$1$2"), // remove braced initializer

	);
	foreach ($openCLReplaceLookup as $item) {
		$content = preg_replace($item['find'], $item['replace'], $content);
	}

	// add c++ side includes
	$cppIncludes = '#ifndef OPENCL_KERNEL_CODE' . PHP_EOL;
	if (basename($copyFile['pathTarget']) != 'common_params_cl.hpp') $cppIncludes .= '#include "common_params_cl.hpp"' . PHP_EOL;
	if (basename($copyFile['pathTarget']) != 'fractal_cl.h') $cppIncludes .= '#include "fractal_cl.h"' . PHP_EOL;
	if (basename($copyFile['pathTarget']) != 'image_adjustments_cl.h') $cppIncludes .= '#include "image_adjustments_cl.h"' . PHP_EOL;
	if (basename($copyFile['pathTarget']) != 'opencl_algebra.h') $cppIncludes .= '#include "opencl_algebra.h"' . PHP_EOL;
	$cppIncludes .= PHP_EOL;
	$cppIncludes .= '#include "src/common_params.hpp"' . PHP_EOL;
	$cppIncludes .= '#include "src/fractal.h"' . PHP_EOL;
	$cppIncludes .= '#include "src/fractal_enums.h"' . PHP_EOL;
	$cppIncludes .= '#include "src/fractparams.hpp"' . PHP_EOL;
	$cppIncludes .= '#include "src/image_adjustments.h"' . PHP_EOL;
	$cppIncludes .= '#endif /* OPENCL_KERNEL_CODE */' . PHP_EOL;

	$content = preg_replace('/(#define MANDELBULBER2_OPENCL_.*)/', '$1' . PHP_EOL . PHP_EOL . $cppIncludes, $content);

	// create copy methods for structs
	preg_match_all('/typedef struct\n{([\s\S]*?\n)}\s([0-9a-zA-Z_]+);/', $content, $structMatches);

	$copyStructs = array();
	foreach ($structMatches[1] as $key => $match) {
		$props = array();
		$structName = trim($structMatches[2][$key]);
		$lines = explode(PHP_EOL, $match);
		foreach ($lines as $line) {
			$line = trim($line);
                        if (preg_match('/^\s*([a-zA-Z0-9_]+)\s([a-zA-Z0-9_\[\]\s\-\+]+);.*/', $line, $lineMatch)) {
				$prop = array();
				$prop['name'] = $lineMatch[2];
				$prop['typeName'] = $lineMatch[1];
				$prop['type'] = $lineMatch[1];
				if (substr($prop['type'], 0, 1) == 's') $prop['type'] = 'struct';
				if (substr($prop['type'], 0, 4) == 'enum') $prop['type'] = 'enum';
				$array = explode('[', $lineMatch[2]);
				array_shift($array);
				if (count($array) > 0) {
					foreach ($array as $index => $e) {
						$size = substr($e, 0, strpos($e, ']'));
						$prop['array'][] = $size;
						// the array will be inited in a loop, so the array index should be the run var in each dimension: i, j, k, ...
						$prop['name'] = str_replace('[' . $size . ']', '[' . chr(105 + $index) . ']', $prop['name']);
					}
				}
				$props[] = $prop;
			}
		}
		$copyStructs[] = getCopyStruct($structName, $props);
	}
	if(count($copyStructs) > 0){
        $content = preg_replace('/(#endif \/\* MANDELBULBER2_OPENCL.*)/',
            PHP_EOL . '#ifndef OPENCL_KERNEL_CODE' . PHP_EOL
            . implode(PHP_EOL, $copyStructs) . '#endif /* OPENCL_KERNEL_CODE */' . PHP_EOL . PHP_EOL . '$1', $content);
	}
	
	// clang-format
	$filepathTemp = PROJECT_PATH . '/tools/.tmp.c';
	file_put_contents($filepathTemp, $content);
	shell_exec(CLANG_FORMAT_EXEC_PATH . ' -i --style=file ' . escapeshellarg($filepathTemp));
	$content = file_get_contents($filepathTemp);
	unlink($filepathTemp); // nothing to see here :)
	patchModificationDate($copyFile['pathTarget'], $content);

	if ($content != $oldContent) {
		if (!isDryRun()) {
			file_put_contents($copyFile['pathTarget'], $content);
		}
		$status[] = noticeString('file changed.');
	}
	return true;
}

function patchModificationDate($filePath, &$content)
{
	$modificationString = getModificationInterval($filePath);
	// patches the modification string
	$content = preg_replace('/Copyright \(C\) [0-9-]+ Mandelbulber Team \s+ §/',
		'Copyright (C) ' . $modificationString . ' Mandelbulber Team ' . str_repeat(' ', 10 - strlen($modificationString)) . ' §', $content);

}

function getCopyStruct($structName, $properties)
{
	$structNameSource = substr($structName, 0, -2);
	$out = 'inline ' . $structName . ' clCopy' . ucfirst($structName) . '(const ' . $structNameSource . '& source){' . PHP_EOL;
	$out .= '	' . $structName . ' target;' . PHP_EOL;
	foreach ($properties as $property) {
		$copyLine = '';

		// if the var is an array, prepend for loops for each dimension
		if (array_key_exists('array', $property) && !empty($property['array'])) {
			foreach ($property['array'] as $index => $size) {
				$x = chr(105 + $index); // i, j, k, ...
				$copyLine .= 'for(int ' . $x . ' = 0; ' . $x . ' < ' . $size . '; ' . $x . '++){';
			}
		}

		$copyLine .= 'target.' . $property['name'] . ' = ';
		switch ($property['type']) {
			// if the var is a struct, copy with the assumed clCopy* function
			case 'struct':
				$copyLine .= 'clCopy' . ucfirst($property['typeName']) . '(source.' . $property['name'] . ');';
				break;
			// the value is an enum, so it has to be casted to target type
			case 'enum':
				$copyLine .= $property['typeName'] . '(source.' . $property['name'] . ');';
				break;
			// the target type is a special opencl type, which has a copy method from opencl_algebra.h
			case 'cl_float3':
				$copyLine .= 'toClFloat3(source.' . $property['name'] . ');';
				break;
			case 'matrix33':
				$copyLine .= 'toClMatrix33(source.' . $property['name'] . ');';
				break;
			case 'cl_int3':
				$copyLine .= 'toClInt3(source.' . $property['name'] . ');';
				break;
			case 'cl_float4':
				$copyLine .= 'toClFloat4(source.' . $property['name'] . ');';
				break;
			// the value can simply be assigned
			default:
				$copyLine .= 'source.' . $property['name'] . ';';
		}

		// close open for loops
		if (array_key_exists('array', $property) && !empty($property['array'])) {
			$copyLine .= str_repeat("}", count($property['array']));
		}
		$out .= '	' . $copyLine . PHP_EOL;
	}

	$out .= '	' . 'return target;' . PHP_EOL;
	$out .= '}' . PHP_EOL;
	return $out;
}


?>

