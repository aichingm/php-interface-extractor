<?php
if (!function_exists("readline")) {
    die("I need the readline extension to be installed and enabled!");
}

$requireFile = trim(readline("Class file: "));
if (!is_file($requireFile)) {
    die("File does not exist!". PHP_EOL);
}
require_once $requireFile;

readline_completion_function(function () {
    $classes = get_declared_classes();
    foreach($classes as &$class){
        $class = str_replace("\\",".",$class);
    }
    return $classes;
});

$class = str_replace(".", "\\", trim(readline("Class name: ")));

if (!class_exists($class)) {
    die("Class does not exist!". PHP_EOL);
}

$outfile = trim(readline("Interface file [$class.php]: "));
if (is_file($outfile)) {
    if (readline("Overwrite existing file [yes/no]? ") != "yes") {
        die("File exists, wont overwrite!". PHP_EOL);
    }
} else if (empty($outfile)) {
    $outfile = $class . ".php";
}

$rClass = new ReflectionClass($class);
$rMethods = $rClass->getMethods();
$interfaceName = trim(readline("Name for the interface [I{$rClass->getShortName()}]: "));
if(empty($interfaceName)){
$interfaceName = "I".$rClass->getShortName();
}
$interfaceContent = "<?php " . PHP_EOL;
$interfaceContent .= "namespace " . $rClass->getNamespaceName() . ";" . PHP_EOL;
$interfaceContent .= "interface " . $interfaceName. "{" . PHP_EOL;

foreach ($rMethods as $rMethod) {
    if ($rMethod->isPublic() && !$rMethod->isStatic()) {
        $interfaceContent .= "   public function ";
        $interfaceContent .= $rMethod->getName() . "(";
        foreach ($rMethod->getParameters() as $rParameter) {
            if ($rParameter->isArray()) {
                $interfaceContent .= "array ";
            }
            if ($rParameter->isCallable()) {
                $interfaceContent .= "callable ";
            }
            if ($rParameter->getClass() != "") {
                $interfaceContent .= "\\" . $rParameter->getClass()->getName() . " ";
            }

            $interfaceContent .= "$" . $rParameter->getName();
            if ($rParameter->isDefaultValueAvailable()) {
                $interfaceContent .= " = " . (is_null($rParameter->getDefaultValue()) ? "null" : str_replace("\n",'',var_export($rParameter->getDefaultValue(), true)));
            }
            if ($rParameter != end($rMethod->getParameters())) {
                $interfaceContent .= ", ";
            }
        }
        $interfaceContent .= ");" . PHP_EOL;
    }
}
$interfaceContent .= "}" . PHP_EOL;
file_put_contents($outfile, $interfaceContent);
