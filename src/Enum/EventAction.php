<?php

namespace SumoCoders\FrameworkCoreBundle\Enum;

enum EventAction: string
{
    case CREATE = 'C';
    case READ = 'R';
    case UPDATE = 'U';
    case DELETE = 'D';
    case EXECUTE = 'E';
}
