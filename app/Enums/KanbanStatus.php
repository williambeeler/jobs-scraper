<?php

namespace App\Enums;

enum KanbanStatus: string
{
    case Backlog = 'backlog';
    case Interested = 'interested';
    case Applied = 'applied';
    case Interview = 'interview';
    case JobWon = 'job_won';
    case Rejected = 'rejected';
    case RejectedCleared = 'rejected_cleared';
}
