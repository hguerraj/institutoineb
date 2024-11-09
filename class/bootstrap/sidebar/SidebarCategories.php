<?php
namespace bootstrap\sidebar;

use bootstrap\nav\Nav;
use common\Utils;

class SidebarCategories extends Sidebar
{
    public $title;
    public $username;
    public $userinfo;
    public $collapsible;
    public $navs = array();

    public function __construct($id, $title, $username, $userinfo, $collapsible = false, $collapsed = false)
    {
        $this->id          = $id;
        $this->title       = $title;
        $this->username    = $username;
        $this->userinfo    = $userinfo;
        $this->collapsible = $collapsible;
        $this->collapsed   = $collapsed;
    }

    public function addNav($nav_id, $nav_class)
    {
        $nav_obj_id = Utils::camelCase($nav_id);
        if ($this->collapsible) {
            if ($this->collapsed) {
                $this->title = '<a class="nav-link dropdown-toggle collapsed" data-bs-toggle="collapse" href="#' . $nav_obj_id . '" role="button" aria-expanded="false" aria-controls="' . $nav_obj_id . '">' . $this->title . '</a>';
            } else {
                $this->title = '<a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#' . $nav_obj_id . '" role="button" aria-expanded="true" aria-controls="' . $nav_obj_id . '">' . $this->title . '</a>';
            }
        }
        $this->$nav_obj_id = new Nav($nav_obj_id, $nav_class);
        $this->navs[] = $this->$nav_obj_id;
    }
}
