<?php

class TopologicalSort {

  /**
   * @param nodes a map from a name to the list of its depenencies
   */
  public static function sort($nodes)
  {
    /**
     * $tempMarked should be unset if the package name has not been seen,
     * should be true if temporarily marked, and false otherwise.
     */
    $tempMarked = array();
    $ordering = array();
    foreach ($nodes as $node => $deps) {
      TopologicalSort::visit($ordering, $tempMarked, $nodes, $node);
    }
    return $ordering;
  }

  private static function visit(&$ordering, &$tempMarked, $nodes, $curNode)
  {
    if (isset($tempMarked[$curNode]) && $tempMarked[$curNode]) {
      throw new \UnexpectedValueException('Cycle in the dependencies of the project');
    }
    if (!isset($tempMarked[$curNode])) {
      $tempMarked[$curNode] = true;
      foreach ($nodes[$curNode] as $node) {
        TopologicalSort::visit($ordering, $tempMarked, $nodes, $node);
      }
      $tempMarked[$curNode] = false;
      $ordering[] = $curNode;
    }
  }

}
