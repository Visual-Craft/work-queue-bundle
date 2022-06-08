Work Queue Symfony bundle
==================================

Background jobs using Beanstalk (Symfony bundle)


Install
-------

    $ composer require visual-craft/work-queue-bundle

Configure
-------
```yaml
##config/packages/visual_craft_work_queue.yaml

visual_craft_work_queue:
    connections:
        default: ~
    queues:
        test_queue:
            connection: default
            worker: 'App\BackgroundJob\Worker\TestWorker'
```

Create Worker
-------
```php
<?php

declare(strict_types=1);

namespace App\BackgroundJob\Worker;

use VisualCraft\WorkQueue\Worker\JobMetadata;
use VisualCraft\WorkQueue\Worker\WorkerInterface;

class TestWorker implements WorkerInterface
{
    public function work($payload, JobMetadata $metadata): void
    {
        //..
    }
}
```

Add service
-------
```yaml
services:
    App\Command\TestCommand:
        arguments:
            - '@visual_craft_work_queue.manager.test_queue'
```

Add queue
-------
```php
//..
private QueueManager $queueManager;

public function __construct(QueueManager $queueManager)
{
    $this->queueManager = $queueManager;
}

//..
    $this->queueManager->add('mixed payload');
//..
```
License
-------

This code is released under the MIT license. See the complete license in the file: `LICENSE`
