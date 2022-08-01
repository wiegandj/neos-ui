<?php
namespace Neos\Neos\Ui\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepository\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;

/**
 * Custom search operation using the Content Graph fulltext search
 *
 * Original implementation: \Neos\Neos\Ui\FlowQueryOperations\SearchOperation
 */
class SearchOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'search';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * We can only handle ContentRepository Nodes.
     *
     * @param mixed $context
     * @return boolean
     */
    public function canEvaluate($context)
    {
        return (isset($context[0]) && ($context[0] instanceof NodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        /** @var array<int,mixed> $context */
        $context = $flowQuery->getContext();
        /** @var NodeInterface $contextNode */
        $contextNode = $context[0];
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $contextNode->getSubgraphIdentity()
        );

        $contentRepository = $this->contentRepositoryRegistry->get($contextNode->getSubgraphIdentity()->contentRepositoryIdentifier);
        $nodeTypeConstraintParser = NodeTypeConstraintParser::create($contentRepository);
        $nodes = $nodeAccessor->findDescendants(
            [$contextNode],
            $nodeTypeConstraintParser->parseFilterString($arguments[1] ?? ''),
            SearchTerm::fulltext($arguments[0] ?? '')
        );
        $flowQuery->setContext(iterator_to_array($nodes));
    }
}
