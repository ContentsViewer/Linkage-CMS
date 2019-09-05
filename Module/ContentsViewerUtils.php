<?php

require_once dirname(__FILE__) . "/Authenticator.php";
require_once dirname(__FILE__) . "/ContentsDatabaseManager.php";
require_once dirname(__FILE__) . "/OutlineText.php";
require_once dirname(__FILE__) . "/CacheManager.php";


function GetContentAuthInfo($contentPath)
{
    $isAuthorized = true;
    $isPublicContent = true;
    $ownerName = Authenticator::GetFileOwnerName($contentPath);

    if ($ownerName !== false) {
        Authenticator::GetUserInfo($ownerName, 'isPublic', $isPublicContent);
    }

    if (!$isPublicContent) {
        // Authenticator::RequireLoginedSession();
        // セッション開始
        @session_start();
        if (Authenticator::GetLoginedUsername() !== $ownerName) {
            $isAuthorized = false;
        }
    }

    return ['ownerName' => $ownerName, 'isPublicContent' => $isPublicContent, 'isAuthorized' => $isAuthorized];
}

/**
 * サーバーとクライアント間の出入口をしっかり.
 */
function H($text)
{
    return htmlspecialchars($text, ENT_QUOTES);
}

function CreateContentHREF($contentPath)
{
    return './?content=' . urlencode($contentPath);
}

function CreateTagDetailHREF($tagName, $metaFileName)
{
    // $url  = empty($_SERVER["HTTPS"]) ? "http://" : "https://";
    // $url .= $_SERVER["HTTP_HOST"]."/tag-list.php";
    $tagName = urlencode($tagName);
    $metaFileName = urlencode($metaFileName);
    return './tag-list.php?group=' . $metaFileName . '&name=' . $tagName;
}

function CreateTagIndexListElement($tagMap, $selectedTagName, $metaFileName)
{
    $listElement = '<ul>';
    foreach ($tagMap as $name => $pathList) {

        $selectedStr = '';
        if ($name == $selectedTagName) {
            $selectedStr = ' class="selected" ';
        }
        $listElement .= '<li><a href="' . CreateTagDetailHREF($name, $metaFileName) . '"' . $selectedStr . '>' . $name . '</a></li>';

    }
    $listElement .= '</ul>';

    return $listElement;
}

function CreateNewBox($tagMap)
{
    $newBoxElement = "<div class='new-box'><ol class='new-list'>";

    if (array_key_exists("New", $tagMap)) {
        $newContents = GetSortedContentsByUpdatedTime($tagMap['New']);
        foreach($newContents as $content){
            $parent = $content->Parent();
            $title = "[" . $content->UpdatedAt() . "] " . $content->Title() .
                     ($parent === false ? '' : ' | ' . $parent->Title());
            $newBoxElement .= "<li><a href='" . CreateContentHREF($content->Path()) . "'>" . $title . "</a></li>";
        }
    }

    $newBoxElement .= "</ol></div>";
    return $newBoxElement;
}

function CreateTagListElement($tagMap, $metaFileName)
{
    $listElement = '<ul class="tag-list">';

    foreach ($tagMap as $name => $pathList) {
        $listElement .= '<li><a href="' . CreateTagDetailHREF($name, $metaFileName) . '">' . $name . '<span>' . count($pathList) . '</span></a></li>';
    }
    $listElement .= '</ul>';

    return $listElement;
}

function CreateUnauthorizedMessageBox(){
    return '
        <div id="error-message-box"><h1>Unauthorized...</h1> <br/>
        対象のコンテンツに対するアクセス権がありません.<br/>
        アクセス権を持つアカウントに再度ログインしてください.<br/>
        <a href="./logout.php?token=' . Authenticator::H(Authenticator::GenerateCsrfToken()) . '&returnTo=' . urlencode($_SERVER["REQUEST_URI"]) .'">
        &gt;&gt;再ログイン&lt;&lt;</a></div>';
}

function CreateHeaderArea($rootContentPath, $metaFileName, $showRootChildren){
    $header = '
            <header id="header-area">
                <div class="logo"><a href="' . CreateContentHREF($rootContentPath) . '">ContentsViewer</a></div>
                <div id="pull-down-menu-button" class="pull-updown-button" onclick="OnClickPullDownButton()"><div class="pull-down-icon"></div></div>
                <div id="pull-up-menu-button" class="pull-updown-button" onclick="OnClickPullUpButton()"><div class="pull-up-icon"></div></div>
                <div class="pull-down-menu">
                <nav class="pull-down-menu-top">
                    <a class="header-link-button" href="' . CreateContentHREF($rootContentPath) . '">フロントページ</a>
                    <a class="header-link-button" href="' . CreateTagDetailHREF('', $metaFileName) . '">タグ一覧</a>
                </nav>
                <nav class="pull-down-menu-content">
            ';
    if($showRootChildren){
        $rootContent = new Content();
        $rootContent->SetContent($rootContentPath);
        if($rootContent !== false){
            $childrenPathList = $rootContent->ChildPathList();
            $childrenPathListCount = count($childrenPathList);

            for ($i = 0; $i < $childrenPathListCount; $i++) {
                $child = $rootContent->Child($i);
                if ($child !== false) {
                    $header .= '<a class="header-link-button" href="' . CreateContentHREF($child->Path()) . '">' . $child->TItle() .'</a>';
                }
            }
        }
    }
    
    $header .= '</nav></div></header>';
    return $header;
}

function CreateTitleField($title, $parents)
{
    $field = '<div id="title-field">';

    //親コンテンツ
    $field .= '<ul class="breadcrumb">';

    $parentsCount = count($parents);
    for ($i = $parentsCount - 1; $i >= 0; $i--) {
        $field .= '<li itemscope="itemscope" itemtype="http://data-vocabulary.org/Breadcrumb">';
        $field .= '<a  href ="' . $parents[$i]['path'] . '" itemprop="url">';
        $field .= '<span itemprop="title">' . $parents[$i]['title'] . '</span></a></li>';
    }
    $field .= '</ul>';

    //タイトル欄
    $field .= '<h1 class="first-heading">' . $title . '</h1>';

    $field .= '</div>';
    return $field;
}

function GetSortedContentsByUpdatedTime($pathList){
    $sorted = [];
    foreach($pathList as $path){
        $content = new Content();
        if(!$content->SetContent($path)){
            continue;
        }

        $sorted[] = $content;
    }

    usort($sorted, function($a, $b){return $b->UpdatedAtTimestamp() - $a->UpdatedAtTimestamp();});
    return $sorted;
}

function GetMessages($contentPath){
    $rootContentsFolder = ContentsDatabaseManager::GetRootContentsFolder($contentPath);
    $messageContent = new Content();
    $messageContent->SetContent($rootContentsFolder . '/Messages');
    if($messageContent === false)
        return [];

    $body = trim($messageContent->Body());
    $body = str_replace("\r", "", $body);
    $lines = explode("\n", $body);
    $messages = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if(substr($line, 0, 2) != '//' && $line != ''){
            $messages[] = $line;
        }
    }
    // Debug::Log(count($messages));
    return $messages;
}

function GetTextHead($text, $wordCount){
    return mb_substr($text, 0, $wordCount) . (mb_strlen($text) > $wordCount ? '...' : '');
}

/**
 * @return array array['summary'], array['body']
 */
function GetDecodedText($content){
    OutlineText\Parser::Init();

    $cache = [];

    // キャッシュの読み込み
    if (CacheManager::CacheExists($content->Path())) {
        $cache = CacheManager::ReadCache($content->Path());
    }

    if (is_null($cache) 
        || !array_key_exists('text', $cache)
        || !array_key_exists('textUpdatedTime', $cache)
        || ($cache['textUpdatedTime'] < $content->UpdatedAtTimestamp())) {
        
        $text = [];
        $context = new OutlineText\Context();
        $context->pathMacros = ContentsDatabaseManager::CreatePathMacros($content->Path());

        $text['summary'] = OutlineText\Parser::Parse($content->Summary(), $context);
        $text['body'] = OutlineText\Parser::Parse($content->Body(), $context);

        $cache['text'] = $text;
        $cache['textUpdatedTime'] = time();
        CacheManager::WriteCache($content->Path(), $cache);

        return $text;
    }
    
    return $cache['text'];
}