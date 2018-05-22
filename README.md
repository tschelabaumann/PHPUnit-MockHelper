# MockHelper - A PHPUnit helper class

The PHPUnit MockHelper solves the problem of configuring an entire class with a slim and easy to read and understand structure.

The class supports:

- Constructor arguments
- 5 different return types (Simple return value, exception, callbacks, consectuive calls and value map)
- 5 Invocation recorders (once, any, never, at and exactly)

## Simple examples

As an example is worth a thousand words (or so nearly), here some examples.

### Simple return value mock

#### Common

```
$carMock = $this->getMockBuilder(Car::class)
    ->setMethods(['getFuelState'])
    ->getMock();
$carMock->expects($this->any())
    ->method('getFuelState')
    ->will($this->returnValue(100));

```

#### With the MockHelper

```
$carMock = $this->getMockObject(Car::class, [
    'getFuelState' => [
        'return' => 100
    ]
]);
```

### Exception

#### Common

```
$carMock = $this->getMockBuilder(Car::class)
    ->setMethods(['getFuelState'])
    ->getMock();
$carMock->expects($this->any())
    ->method('getFuelState')
    ->will($this->throwException(new Exception('Tank exploded!')));

```

#### With the MockHelper

```
$carMock = $this->getMockObject(Car::class, [
    'getFuelState' => [
        'return' => new Exception('Tank exploded!'),
        'return_type' => MockHelper::RETURN_TYPE_EXCEPTION
    ]
]);
```

### Callbacks

#### Common

```
$carMock = $this->getMockBuilder(Car::class)
    ->setMethods(['getFuelState'])
    ->getMock();
$carMock->expects($this->any())
    ->method('getFuelState')
    ->will($this->returnCallback(function() { 
        return 100; 
    }));

```

#### With the MockHelper

```
$carMock = $this->getMockObject(Car::class, [
    'getFuelState' => [
        'return' => function() { 
            return 100; 
        },
        'return_type' => MockHelper::RETURN_TYPE_CALLBACK
    ]
]);
```

### consectuive calls

#### Common

```
$carMock = $this->getMockBuilder(Car::class)
    ->setMethods(['getFuelState'])
    ->getMock();
$carMock->expects($this->any())
    ->method('getFuelState')
    ->will($this->onConsecutiveCalls(2, 3, 5, 7));

```

#### With the MockHelper

```
$carMock = $this->getMockObject(Car::class, [
    'getFuelState' => [
        'return' => [2, 3, 5, 7],
        'return_type' => MockHelper::RETURN_TYPE_CONSECUTIVE
    ]
]);
```

### Value map

#### Common

```
$carMock = $this->getMockBuilder(Car::class)
    ->setMethods(['getFuelState'])
    ->getMock();
$carMock->expects($this->any())
    ->method('getFuelState')
    ->will($this->returnValueMap([
            ['a', 'b', 'c', 'd'],
            ['e', 'f', 'g', 'h']
    ]));

```

#### With the MockHelper

```
$carMock = $this->getMockObject(Car::class, [
    'getFuelState' => [
        'return' => [
            ['a', 'b', 'c', 'd'],
            ['e', 'f', 'g', 'h']
        ],
        'return_type' => MockHelper::RETURN_TYPE_VALUE_MAP
    ]
]);
```

## More complex examples

So far so good, here the MockHelper doesn't save so much code writing, but it becomes more clear with a more complex example and the trust in the auto return type detection.

### Mocking multiple methods with a combination of different return types

Imagine you have a helper method to create dynamic mocks:

#### Common

```
public function getCarMock($withFuelState = null, $withEngineState = null)
{
    $stateMock = $this->getMockBuilder(ServiceState::class)
        ->setMethods(['getStatusCode'])
        ->getMock();

    $stateMock->expects($this->any())
        ->method('getStatusCode')
        ->will($this->returnValue('OK'));

    $methodsToMock = ['getState', 'getGear', 'startBlinker', 'stopBlinker'];

    if($withFuelState) {
        $methodsToMock[] = 'getFuelState';
    }

    if($withEngineState) {
        $methodsToMock[] = 'getEngineState';
    }

    $carMock = $this->getMockBuilder(Car::class)
        ->setMethods($methodsToMock)
        ->getMock();

    $carMock->expects($this->any())
        ->method('getState')
        ->will($this->returnValue($stateMock));

    $carMock->expects($this->any())
        ->method('getGear')
        ->will($this->returnValue(5));

    $carMock->expects($this->any())
        ->method('startBlinker')
        ->will($this->returnValue(true));

    $carMock->expects($this->any())
        ->method('stopBlinker')
        ->will($this->returnValue(true));

    if($withFuelState) {
        $carMock->expects($this->any())
            ->method('getFuelState')
            ->will($this->returnValue($withFuelState));
    }

    if($withEngineState) {
        $carMock->expects($this->any())
            ->method('getEngineState')
            ->will($this->returnValue($withEngineState));
    }

    return $carMock;
}

```


#### With the MockHelper

```
public function getCarMock($withFuelState = null, $withEngineState = null)
{
    $stateMock = $this->getMockObject('State::class', ['getStatusCode' => 'OK']);

    $methodsToMock = [
        'getState' => $stateMock, 
        'getGear' => 5, 
        'startBlinker' => true, 
        'stopBlinker' => true
    ];

    if($withFuelState) {
        $methodsToMock['getFuelState'] = $withFuelState;
    }

    if($withEngineState) {
        $methodsToMock['getEngineState'] = $withEngineState;
    }

    $carMock = $this->getMockBuilder(Car::class)
        ->setMethods($methodsToMock)
        ->getMock();

    $carMock = $this->getMockObject(Car::class, $methodsToMock);

    return $carMock;
}

```






