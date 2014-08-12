<?php

/*
 *  Copyright (c) 2014, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
