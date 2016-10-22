<?php

/**
 * MIT License
 *
 * Copyright (c) 2016 Bernardo Secades
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace BernardoSecades\QueueSystemBundle\DependencyInjection\Compiler;

use BernardoSecades\QueueSystemBundle\Queue\Queue;
use BernardoSecades\QueueSystemBundle\Util\QueueUtil;
use BernardoSecades\QueueSystemBundle\ValueObject\Config;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * ServiceCompilerPass.
 *
 * @author bernardosecades <bernardosecades@gmail.com>
 */
class ServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $nameQueues = $container->getParameter(Config::PREFIX.'.queues');

        foreach ($nameQueues as $name) {
            $nameQueue   = sprintf('%s', $name);
            $nameService = QueueUtil::getServiceName($nameQueue);
            $arguments = [
                $nameQueue,
                new Reference(Config::PREFIX.'.redis.driver'),
                new Reference('serializer')

            ];

            $definition = new Definition(Queue::class, $arguments);
            $container->setDefinition($nameService, $definition);
        }
    }
}
