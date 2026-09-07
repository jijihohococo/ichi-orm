<?php

use IchiORMTests\Fixtures\Blog;
use IchiORMTests\Fixtures\TestObserver;

class ObserverTest extends DriverTestCase
{
    public function testObserverCanBeRegistered()
    {
        TestObserver::reset();
        $result = Blog::observe(new TestObserver());

        $this->assertTrue($result === null || is_object($result));
    }

    public function testObserverSubjectIsReachableFromQueryBuilder()
    {
        $builder = new JiJiHoHoCoCo\IchiORM\QueryBuilder\QueryBuilder();
        $this->assertNull($builder->getObserverSubject());
    }
}
