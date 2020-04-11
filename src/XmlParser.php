<?php declare(strict_types=1);

namespace Przeslijmi\WebDavApi;

use stdClass;

/**
 * XML Parser for my needs.
 */
class XmlParser
{

    /**
     * All nodes as returned by `xml_parse_into_struct`.
     *
     * @var array
     */
    private $nodes;

    /**
     * Indexes as returned by `xml_parse_into_struct`.
     *
     * @var array
     */
    private $index;

    /**
     * Final `stdClass` object - result of this class.
     *
     * @var stdClass
     */
    private $stdClass;

    /**
     * Extra index of elements on each level (for looking for parents).
     *
     * @var array
     */
    private $indexOfNodesInLevels = [];

    /**
     * Constructor.
     *
     * @param string $xml Full structured xml document.
     */
    public function __construct(string $xml)
    {

        // Create parser and read data.
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $this->nodes, $this->index);
        xml_parser_free($parser);

        // Create empty result.
        $this->stdClass = new stdClass();
    }

    /**
     * Return given XML string as `stdClass` object.
     *
     * @return stdClass
     */
    public function getAsObject() : stdClass
    {

        // Work on each node.
        foreach ($this->nodes as $nodeId => $node) {

            // If this is closing node - ignore it.
            if ($node['type'] === 'close') {
                continue;
            }

            // On which level this node is placed.
            // When I know it - I know where is it's parent - ie. last key on previous level.
            $levelToFind = ( $node['level'] - 1 );

            // Depending on parent level.
            if ($levelToFind === 0) {

                // If parent's level is 0 - it is a top level.
                $parent = $this->stdClass;

            } else {

                // Find last key in parents level - it will be parent.
                $lastKey = array_key_last($indexOfNodesInLevels[$levelToFind]);
                $parent  = $indexOfNodesInLevels[$levelToFind][$lastKey];

                // Parent can not have any childrens object - lets create it.
                if (isset($parent->children) === false) {
                    $parent->children = new stdClass();
                }

                // Use it locally below - this is parent.
                $parent = $parent->children;
            }

            // Prepare work on this node and define it.
            $child        = new stdClass();
            $child->tag   = $node['tag'];
            $child->type  = $node['type'];
            $child->level = $node['level'];

            // Add value if neccesary.
            if (isset($node['value']) === true) {
                $child->value = $node['value'];
            }

            // Add attributes if neccesary.
            if (isset($node['attributes']) === true) {
                $child->attributes = (object) $node['attributes'];
            }

            // Save this in index - it will be used as parent for his children.
            $indexOfNodesInLevels[$node['level']][$nodeId] = $child;

            // Prepare node for children with this name (tag).
            if (isset($parent->{$node['tag']}) === false) {
                $parent->{$node['tag']} = [];
            }

            // Add this child to parent.
            $parent->{$node['tag']}[] = $child;
        }//end foreach

        return $this->stdClass;
    }
}
