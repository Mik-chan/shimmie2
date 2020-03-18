<?php declare(strict_types=1);

class VideoFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_video";

    public $key = self::KEY;
    public $name = "Handle Video";
    public $authors = ["velocity37"=>"velocity37@gmail.com",self::SHISH_NAME=>self::SHISH_EMAIL, "jgen"=>"jeffgenovy@gmail.com", "im-mi"=>"im.mi.mail.mi@gmail.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Handle FLV, MP4, OGV and WEBM video files.";
    public $documentation =
"Based heavily on \"Handle MP3\" by Shish.<br><br>
FLV: Flash player<br>
MP4: HTML5 with Flash fallback<br>
OGV, WEBM: HTML5<br>
MP4's flash fallback is forced with a bit of Javascript as some browsers won't fallback if they can't play H.264.
In the future, it may be necessary to change the user agent checks to reflect the current state of H.264 support.<br><br>";
}
