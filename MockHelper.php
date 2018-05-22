<?php

namespace Paymill\LibraryBackend\PhpUnit;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Exception;

/**
 * Unit test mock object helper
 */
class MockHelper extends PHPUnit_Framework_TestCase
{
    /**
     * Allowed return types
     */
    const RETURN_TYPE_EXCEPTION = 'exception';
    const RETURN_TYPE_CALLBACK = 'callback';
    const RETURN_TYPE_CONSECUTIVE = 'consecutive';
    const RETURN_TYPE_VALUE_MAP = 'value_map';

    /**
     * Allowed invocation types
     */
    const INVOC_ANY = 'any';
    const INVOC_ONCE = 'once';
    const INVOC_NEVER = 'never';
    const INVOC_AT = 'at';
    const INVOC_EXACTLY = 'exactly';
    
    /**
     * Get Mock object
     *
     * @param string $class Class to mock with namespace
     * @param array|null $methods Methods to mock with key return => return value and key call with
     *                            ether once, any, or a numeric value which has to be matched exactly
     * @param array|null Sets the mock constructor args or disables mock constructor when null is given
     *
     * @return PHPUnit_Framework_MockObject_MockObject Mock object
     */
    public function getMockObject($class, $methods = null, $construct = null)
    {
        /** @var \PHPUnit_Framework_MockObject_MockBuilder */
        $mockBuilder = $this->getMockBuilder($class)
            ->setMethods(is_array($methods) ? array_keys($methods) : $methods);

        if($construct === null) {
            $mockBuilder->disableOriginalConstructor();
        } elseif(!empty($construct)) {
            $mockBuilder->setConstructorArgs($construct);
        }

        $mock = $mockBuilder->getMock();

        if(!$methods) {
            return $mock;
        }

        foreach ($methods as $method => $definition) {

            if (is_null($definition)) {
                continue;
            }

            if(!isset($definition['return_type'])) {
                if(is_callable($definition['return'])) {
                    $definition['return_type'] = self::RETURN_TYPE_CALLBACK;
                } elseif(is_object($definition['return']) && is_subclass_of($definition['return'], Exception::class)) {
                    $definition['return_type'] = self::RETURN_TYPE_EXCEPTION;
                }
            }

            if (isset($definition['return_type'])) {
                switch ($definition['return_type']) {
                    case self::RETURN_TYPE_VALUE_MAP:
                        $return = $this->returnValueMap($definition['return']);
                        break;
                    case self::RETURN_TYPE_CALLBACK:
                        $return = $this->returnCallback($definition['return']);
                        break;
                    case self::RETURN_TYPE_CONSECUTIVE:
                        $return = call_user_func_array([$this, 'onConsecutiveCalls'], $definition['return']);
                        break;
                    case self::RETURN_TYPE_EXCEPTION:
                        $return = $this->throwException($definition['return']);
                        break;
                    default:
                        $return = $this->returnValue($definition['return']);
                        break;
                }
            } else {
                $return = $this->returnValue($definition['return']);
            }

            $call = $this->any();
            if (isset($definition['call'])) {
                switch ($definition['call']) {
                    case self::INVOC_ONCE:
                        $call = $this->once();
                        break;
                    case self::INVOC_ANY:
                        $call = $this->any();
                        break;
                    case self::INVOC_AT:
                        // return should contain array with "at" values
                        break;
                    case self::INVOC_NEVER:
                        $call = $this->never();
                        break;
                    default:
                        if(is_numeric($definition['call'])) {
                            $call = $this->exactly($definition['call']);
                        }
                        break;
                }
            }

            // Add at-matchers
            if (isset($definition['call']) && $definition['call'] == self::INVOC_AT) {
                foreach ($definition['return'] as $at => $value) {
                    $mock->expects($this->at($at))->method($method)->will($this->returnValue($value));
                }
            } else if (isset($definition['arguments'])) {
                $expects = $mock->expects($call);
                $expects->method($method)->will($return);
                call_user_func_array([$expects, 'with'], $definition['arguments']);
            } else {
                $mock->expects($call)->method($method)->will($return);
            }
        }

        return $mock;
    }
}
