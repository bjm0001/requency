<?php
/**
 * Created by QiLin.
 * User: NO.01
 * Date: 2019/7/22
 * Time: 15:10
 */

namespace Application\Core\insuranceApi\common;


use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Exception;

class Frequency implements InjectionAwareInterface
{
    protected $_di;

    /**
     * Author:QiLin
     * @var
     */
    public $keyword;

    /**
     * Author:QiLin
     * @var
     */
    public $duration;
    /**
     * Author:QiLin
     * @var
     */
    public $requestNumber;

    public $redisServe;

    public $error;

    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        // TODO: Implement setDI() method.
        $this->_di = $dependencyInjector;
    }


    public function getDI()
    {
        // TODO: Implement getDI() method.
        return $this->_di;
    }

    /**
     * Author:QiLin
     * @return mixed
     * @throws Exception
     */
    public function getRedisServe()
    {
        if ($this->redisServe) {
            return $this->redisServe;
        }
        $this->redisServe = $this->getDI()->get('frequencyCache');
        if (!$this->redisServe) {
            throw new Exception('请设置frequencyCache serve');
        }
        return $this->redisServe;
    }


    public function __construct($options = [])
    {
        if (isset($options['keyword'])) {
            $this->keyword = $options['keyword'];
        }
        if (isset($options['duration'])) {
            $this->duration = (int)$options['duration'];
        }
        if (isset($options['requestNumber'])) {
            $this->requestNumber = (int)$options['requestNumber'];
        }
    }

    public function setKey(string $key = '')
    {
        $this->keyword = $key;
    }

    public function setDuration(int $duration = 0)
    {
        $this->duration = $duration;
    }

    public function setRequestNumber(int $requestNumber = 0)
    {
        $this->requestNumber = $requestNumber;
    }

    /**
     * Author:QiLin
     * @param bool $trim
     */
    public function record(bool $trim = true)
    {
        $this->redisServe->lpush($this->keyword, time());
        if ($trim) {
            $this->redisServe->ltrim($this->keyword, 0, $this->requestNumber - 1);
        }
    }

    /**
     * Author:QiLin
     * @return bool
     */
    public function check()
    {
        try {
            if (!$this->keyword) {
                throw new Exception('请设置keyword');
            }
            if (!$this->duration) {
                throw new Exception('请设置duration');
            }
            if (!$this->requestNumber) {
                throw new Exception('请设置requestNumber');
            }
            $this->getRedisServe();
            $lLength = $this->redisServe->llen($this->keyword);
            if ($lLength < $this->requestNumber) {
                $this->record(false);
            } else {
                $firstTime = $this->redisServe->lindex($this->keyword, $lLength - 1);
                if ((time() - $firstTime) < $this->duration) {
                    throw new Exception('达到最大请求，请稍后再试');
                };
                $this->record();
            }
        } catch (\Exception $exception) {
            $this->error = $exception->getMessage();
            return false;
        }
        return true;
    }

    public function getError()
    {
        return $this->error;
    }

}