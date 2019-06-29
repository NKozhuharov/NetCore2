<?php
class Menu
{
    /**
     * @var string
     * Path name to display in the header
     */
    private $pathName;

    /**
     * @var string
     * An imploded copy of the $Core->Rewrite->urlBreakdown
     */
    private $url = '';

    /**
     * @var string
     * A copy of the $Core->Rewrite->controllerPath
     */
    private $controllerPath = '';

    /**
     * @var string
     * Use this field to specify the link field
     */
    protected $linkField = 'link';

    /**
     * @var string
     * Use this field to specify the path field
     */
    protected $pathField = 'controller_path';

    /**
     * Creates a new instance of the Menu class
     */
    public function __construct()
    {
        global $Core;

        $this->url = '/'.implode('/', $Core->Rewrite->urlBreakdown);

        $this->controllerPath = $Core->Rewrite->controllerPath;
    }

    /**
     * Returns parents of element of array
     * @param array $tree - formed tree of pages from database
     * @param int $id - the id of the element
     * @param array $parents - for function storage
     * @return array - parents of the element if any
     */
    private function findParents(array $tree, int $id, array $parents = array())
    {
        global $Core;

        $parent = $Core->globalFunctions->arraySearch($tree, 'id', $id);

        if (isset($parent[0]['parent_id']) && $parent[0]['parent_id']) {
            $parents[] =  $parent[0]['parent_id'];
            return $this->findParents($tree, $parent[0]['parent_id'], $parents);
        }

        $parents = array_reverse($parents);

        return $parents;
    }

    /**
     * Returns children of element of array
     * @param array $tree - formed tree of pages from database
     * @param int $id - the id of the element
     * @param array $children - for function storage
     * @return array - children of the element if any
     */
    private function findChildren(array $tree, int $id, array $children = array())
    {
        global $Core;

        $child = $Core->globalFunctions->arraySearch($tree, 'id', $id);

        if (isset($child[0]['children']) && $child[0]['children']) {
            foreach ($child[0]['children'] as $c) {
                $children[] = $c['id'];
                $children = $this->findChildren($tree, $c['id'], $children);
            }
        }

        return $children;
    }

    /**
     * Returns formed array of elements by parent id
     * @param array $elements - array of pages from database
     * @param int $parentId - only for the function it comes from $elements values
     * @param int $level - only for the function
     * @return array - formed tree
     */
    public function buildTree(array $elements, $parentId = 0, $level = 0)
    {
        $branch = array();
        $level++;

        foreach ($elements as $k => $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id'], $level);

                if ($children) {
                    $element['children'] = $children;
                } else {
                    $element['children'] = array();
                }

                $element['level']       = $level;
                $element['parentsIds']  = $this->findParents($elements, $element['id']);
                $element['childrenIds'] = $this->findChildren($element, $element['id']);
                $branch[$k]             = $element;
            }
        }

        return $branch;
    }

    /**
     * Draws menu HTML from formed array of elements
     * @param array $tree - formed array of elements
     * @param bool $isResp - determinates if the menu should be responsive
     */
    private function formTree(array $tree, bool $isResp = null)
    {
        global $Core;

        if ($isResp === null) {
            $isResp = true;
        }

        $parents = $Core->globalFunctions->arraySearch($tree, $this->pathField, $this->controllerPath);

        if (isset($parents[0], $parents[0]['parentsIds'])) {
            $parents = $parents[0]['parentsIds'];
        }

        foreach($tree as $t) {
            $name = $Core->language->{mb_strtolower(str_replace(' ', '_', $t['name']))};

            if(empty($name)){
                continue;
            }

            if ($t['children']) {
                $subMenu = false;
                foreach ($t['children'] as $child) {
                    if ($child['name']) {
                        $subMenu = true;

                        break;
                    }
                }
            ?>
                <li
                    id="<?php echo $t['id']; ?>"
                    title="<?php echo $name; ?>"
                    class="
                    <?php echo
                        ($t[$this->linkField] == $this->url ? ' is-current' : '' ).
                        ($subMenu ? ' hasMenu' : ' ').
                        ($isResp ? ' respPad' : '').
                        (in_array($t['id'], $parents) ? ' active' : '');
                    ?>"
                >
                    <?php if ($t[$this->linkField]) { ?>
                        <a class=" <?php echo $subMenu ? 'menu-name' : '';?>" href="<?php echo $t[$this->linkField]; ?>">
                            <span class="menu-name-icon"><?php echo $t['icon']; ?></span><?php echo $name; ?>
                        </a>
                    <?php } else { ?>
                        <div class="menu-name"><span class="menu-name-icon"><?php echo $t['icon']; ?></span><?php echo $name; ?></div>
                    <?php } ?>
                    <ul class="<?php echo (in_array($t['id'], $parents) ? 'opened' : '').($isResp ? ' resp' : ''); ?>">
                        <?php $this->formTree($t['children'], $isResp); ?>
                    </ul>
                </li>
            <?php } else {
                ?>
                <li
                    id="<?php echo $t['id']; ?>"
                    title="<?php echo $name; ?>"
                    class="noMenu <?php echo $t[$this->linkField] == $this->url ? ' is-current' : ''; ?>"
                >
                    <?php if ($t[$this->linkField]) { ?>
                        <a href="<?php echo $t[$this->linkField]; ?>"><span class="menu-name-icon"><?php echo $t['icon']; ?></span><?php echo $name; ?></a>
                    <?php } else { ?>
                        <div class="menu-name"><span class="menu-name-icon"><?php echo $t['icon']; ?></span><?php echo $name; ?></div>
                    <?php } ?>
                </li>
            <?php
            }
        }
    }

    /**
     * Overrides the default path name
     * @param string $customPathName - the new path name
     */
    public function setCustomPathName(string $customPathName)
    {
        $this->pathName = $customPathName;
    }

    /**
     * Gets the current path name to display in the header
     * @return string
     */
    public function getPathName()
    {
        if (empty($this->pathName)) {
            return $this->getFullPathName();
        }

        return $this->pathName;
    }

    /**
     * Returns translated path to file separated with delimiter
     * @param array $pages - array of pages from database
     * @param string $delimiter - delimiter symbol
     * @return string
     */
    public function getFullPathName()
    {
        global $Core;

        $delimiter = ' / ';

        $pages = $Core->{$Core->userModel}->pages;

        $fullPageName = false;

        if ($pages) {
            $pages = $this->buildTree($pages);

            $currentPage = $Core->globalFunctions->arraySearch($pages, $this->linkField, $this->url);

            if (isset($currentPage[0], $currentPage[0]['parentsIds'])) {
                $parents = $currentPage[0]['parentsIds'];

                foreach ($parents as $p) {
                    $fullPageName .= $Core->Language->{
                        (mb_strtolower(str_replace(' ', '_', $Core->globalFunctions->arraySearch($pages, 'id', $p)[0]['name'])))
                    }.$delimiter;
                }
            }

            if (isset($currentPage[0], $currentPage[0]['name'])) {
                $fullPageName .= $Core->Language->{(mb_strtolower(str_replace(' ', '_', $currentPage[0]['name'])))};
            }

            return $fullPageName;
        }

        return '';
    }

    /**
     * If class is false menu will auto expand aside and if menu width is bigger than its parent, elements goes one under another, max width of element is 250px.
     * Else every element goes one under another, max width of element is 100%.
     * Width comes from the css.
     *
     * Returns the html of menu formed of pages from database by parent id
     * @param array $pages - array of pages from database
     * @param string $class - additional class to add to menu; allResp fror responsive menu
     * @return string
     */
    public function getMenu(array $pages, string $class = null)
    {
        global $Core;

        if ($class === null) {
            $class = 'allResp';
        }

        ob_start();
        ?>
        <div class="mega-menu <?php echo $class; ?>">
            <ul class="mega-menu-in">
                <?php
                if ($pages) {
                    $this->formTree($this->buildTree($pages), $class);
                } else {
                    echo 'No pages';
                }
                ?>
            </ul>
        </div>
        <?php
        $menu = ob_get_contents();
        ob_end_clean();

        return $menu;
    }
}
