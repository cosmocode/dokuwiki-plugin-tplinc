<?php
/**
 * DokuWiki Plugin tplinc (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_tplinc extends DokuWiki_Plugin {

    protected $file = DOKU_CONF . 'tplinc.conf';

    /**
     * Load the current assignments into an array
     *
     * @return array
     */
    public function loadAssignments() {
        $assignments = array();
        if(!file_exists($this->file)) return $assignments;

        $data = file($this->file);
        foreach($data as $line) {
            //ignore comments (except escaped ones)
            $line = preg_replace('/(?<![&\\\\])#.*$/', '', $line);
            $line = str_replace('\\#', '#', $line);
            $line = trim($line);
            if(empty($line)) continue;
            $assignments[] = array_map('trim', explode("\t", $line, 4));
        }

        return $assignments;
    }

    /**
     * The same as loadAssignments but uses caching
     *
     * @return array
     */
    public function getAssignments() {
        static $assignements = null;
        if($assignements === null) $assignements = $this->loadAssignments();
        return $assignements;
    }

    /**
     * Save new assignment data
     *
     * @param array $data as returned by loadAssignment
     * @return bool
     */
    public function saveAssignments($data) {
        $content = '';

        foreach($data as $row) {
            $row = array_map('trim', $row);
            if($row[0] === '' || $row[1] === '') continue;
            if(count($row) < 4) $row[3] = 0;

            $content .= join("\t", $row) . "\n";
        }

        return io_saveFile($this->file, $content);
    }

    /**
     * Get a list of pages that should be included at the given $location
     *
     * @param string $location
     * @param null|string $id the ID to check against, null for global $ID
     * @return array list of pages to include
     */
    public function getIncludes($location, $id = null) {
        global $ID;
        if($id === null) $id = $ID;
        $id = cleanID($id);
        $ns = getNS($id);
        $pns = ":$ns:";

        $assignments = $this->getAssignments();
        $pages = array();

        foreach($assignments as $row) {
            list($pattern, $page, $loc, $skipacl) = $row;
            if($loc != $location) continue;
            $page = $this->matchPagePattern($pattern, $id, $page, $pns);
            if($page === false) continue;
            $exists = false;
            resolve_pageid($ns, $page, $exists);
            if(!$exists) continue;
            if(!$skipacl && auth_quickaclcheck($page) < AUTH_READ) continue;
            $pages[] = $page;
        }

        array_unique($pages);
        return $pages;
    }

    /**
     * Render the include pagesfor the given $location
     *
     * @param $location
     * @param null|string $id the ID to check against, null for global $ID
     * @return string the rendered XHTML
     */
    public function renderIncludes($location, $id = null) {
        $pages = $this->getIncludes($location, $id);
        $content = '';
        foreach($pages as $page) {
            $content .= p_wiki_xhtml($page, '', false);
        }
        return $content;
    }

    /**
     * Get the locations supported by the template
     *
     * The template needs to implement the apropriate event hook
     *
     * @return array
     */
    public function getLocations() {
        $data = array('' => $this->getLang('unknown'));
        $event = new Doku_Event('PLUGIN_TPLINC_LOCATIONS_SET', $data);
        $event->advise_before(false);
        asort($data);
        $event->advise_after();

        return $data;
    }

    /**
     * Check if the given pattern matches the given page id
     *
     * @param string $pattern the pattern to check against
     * @param string $id the cleaned pageid to check
     * @param string $page the page to include on success - may contain regexp placeholders
     * @param string|null $pns optimization, the colon wrapped namespace of the page, set null for automatic
     * @return string|bool the page as matched (with placeholders replaced) or false if the pattern does not match
     */
    protected function matchPagePattern($pattern, $id, $page, $pns = null) {
        if(trim($pattern, ':') == '**') return $page; // match all

        // regex patterns
        if($pattern{0} == '/') {
            if(preg_match($pattern, ":$id", $matches)) {
                $len = count($matches);
                for($i = $len - 1; $i >= 0; $i--) {
                    $page = str_replace('$' . $i, $matches[$i], $page);
                }
                return $page;
            };
            return false;
        }

        if(is_null($pns)) {
            $pns = ':' . getNS($id) . ':';
        }

        $ans = ':' . cleanID($pattern) . ':';
        if(substr($pattern, -2) == '**') {
            // upper namespaces match
            if(strpos($pns, $ans) === 0) {
                return $page;
            }
        } else if(substr($pattern, -1) == '*') {
            // namespaces match exact
            if($ans == $pns) {
                return $page;
            }
        } else {
            // exact match
            if(cleanID($pattern) == $id) {
                return $page;
            }
        }

        return false;
    }

}

// vim:ts=4:sw=4:et:
