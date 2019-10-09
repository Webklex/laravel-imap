<?php
/*
 * File: folder_structure.blade.php
 * Category: View
 * Author: M.Goldenbaum
 * Created: 15.09.18 19:53
 * Updated: -
 *
 * Description:
 *  -
 */

/**
 * @var \Webklex\IMAP\Support\FolderCollection $paginator
 * @var \Webklex\IMAP\Folder $oFolder
 */

?>
<table>
    <thead>
    <tr>
        <th>Folder</th>
        <th>Unread messages</th>
    </tr>
    </thead>
    <tbody>
    @if($paginator->count() > 0)
        @foreach($paginator as $oFolder)
            <tr>
                <td>{{$oFolder->name}}</td>
                <td>{{$oFolder->search()->unseen()->leaveUnread()->setFetchBody(false)->setFetchAttachment(false)->get()->count()}}</td>
            </tr>
        @endforeach
    @else
        <tr>
            <td colspan="4">No folders found</td>
        </tr>
    @endif
    </tbody>
</table>

{{$paginator->links()}}