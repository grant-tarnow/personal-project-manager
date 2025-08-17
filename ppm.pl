#!/usr/bin/perl

use strict;
use warnings;
use DBI;
use File::Temp qw/ tempfile /;

my $dbfile = glob("~/.ppm/ppm.sqlite3");
my $dbh = DBI->connect("dbi:SQLite:dbname=$dbfile","","", {
   PrintError       => 0,
   RaiseError       => 1,
   AutoCommit       => 1,
   FetchHashKeyName => 'NAME_lc',
}); 

my ($command, $item) = @ARGV;

if (not defined $command or $command =~ /^p/) {
    if (defined $item) {
        if ($item =~ /^\d+$/) {
            projectUI($item);
        } else {
            print "Second argument must be a positive integer.\n";
            projectList("standard");
        }
    } else {
        projectList("standard");
    }

} elsif ($command eq "help") {
    help();

}

$dbh->disconnect;

sub help {
    print "Help function!\n";
}

sub projectList {
    my ($view) = @_;

    my $sth;
    if ($view eq "standard") {
        $sth = $dbh->prepare("SELECT * FROM projects WHERE (status = 'NOT STARTED' OR status = 'IN PROGRESS') ORDER BY priority");
    } elsif ($view eq "active") {
        $sth = $dbh->prepare("SELECT * FROM projects WHERE (status = 'IN PROGRESS') ORDER BY priority");
    } elsif ($view eq "hold") {
        $sth = $dbh->prepare("SELECT * FROM projects WHERE (status = 'ON HOLD') ORDER BY priority");
    } elsif ($view eq "incomplete") {
        $sth = $dbh->prepare("SELECT * FROM projects WHERE (status != 'COMPLETE') ORDER BY priority");
    } elsif ($view eq "complete") {
        $sth = $dbh->prepare("SELECT * FROM projects WHERE (status = 'COMPLETE' or status = 'ABANDONED') ORDER BY priority");
    } elsif ($view eq "all") {
        $sth = $dbh->prepare("SELECT * FROM projects ORDER BY priority");
    }
    execWithCheck($sth->execute());

    my @results;
    print "\nID\tPRI\tSTATUS\t\tTITLE\n";
    while (my $row = $sth->fetchrow_hashref) {
        push(@results, $row);
        print "[$row->{project_id}]\t$row->{priority}\t$row->{status}\t$row->{title}\n";
    }

    while (1) {
        print "\n--------------------------------------------------------------------------\n";
        my $instr = prompt("(#)proj. (a)dd (s)tandard in(p)rog. (h)old (i)ncomp. (c)omp. a(l)l (q)uit:");

        if ($instr =~ /^\d+/) {
            projectUI($instr);
            last;
        } elsif ($instr =~ /^a/) {
            addProject();
            projectList("standard");
            last;
        } elsif ($instr =~ /^s/) {
            projectList("standard");
            last;
        } elsif ($instr =~ /^p/) {
            projectList("active");
            last;
        } elsif ($instr =~ /^h/) {
            projectList("hold");
            last;
        } elsif ($instr =~ /^i/) {
            projectList("incomplete");
            last;
        } elsif ($instr =~ /^c/) {
            projectList("complete");
            last;
        } elsif ($instr =~ /^l/) {
            projectList("all");
            last;
        } else {
            print "Invalid command. Please try again.\n";
        }
    }
}

sub projectUI {
    my ($pid) = @_;

    my $prj = getProject($pid);
    my $tasks = getTasksOfProject($pid);
    my $notes = getNotesOfProject($pid);
    my $links = getLinksOfProject($pid);

    my $num_tasks = @{$tasks};
    my $num_links = @{$links};

    my $num_notes = @{$notes};
    my $lastNoteTime = "";
    if ($num_notes) {
        $lastNoteTime = "\t\t[$notes->[-1]{created}]";
    }

    my $next; # saved later in case user goes to that task view

    print "\n=================\n";
    print "PROJECT PAGE FOR: $prj->{title}\n";
    print "=================\n";
    print "PRI:    $prj->{priority}\n";
    print "STATUS: $prj->{status}\t[$prj->{updated}]\n";
    print "NOTES:  $num_notes$lastNoteTime\n";
    print "LINKS:  $num_links\n";
    print "TASKS:  $num_tasks\n";
    print "\tNEXT: ";
    foreach my $task (@{$tasks}) {
        if ($task->{next} == 1) {
            $next = $task;
            print "$task->{description}\n";
            print "\t($task->{status})\n";
        }
    }

    while (1) {
        print "\n----------------------------------------------------------------------------------------------\n";
        my $instr = prompt("(n)otes, (t)asks, (l)inks, n(e)xt, ti(m)eline, (s)tart, (c)omplete, (h)old, (a)bandon, (q)uit:");

        if ($instr =~ /^n/) {
            projectNotesView($prj, $notes);
            last;
        } elsif ($instr =~ /^t/) {
            taskList($prj, $tasks);
            last;
        } elsif ($instr =~ /^l/) {
            linkList($prj);
            last;
        } elsif ($instr =~ /^e/) {
            taskUI($next->{task_id});
            last;
        } elsif ($instr =~ /^m/) {
            projectTimeline($prj);
            projectUI($pid);
            last;
        } elsif ($instr =~ /^s/) {
            addNote("project_id", $pid);
            projectStatusify($prj, "IN PROGRESS");
            projectUI($pid);
            last;
        } elsif ($instr =~ /^c/) {
            addNote("project_id", $pid);
            projectStatusify($prj, "COMPLETE");
            projectUI($pid);
            last;
        } elsif ($instr =~ /^h/) {
            addNote("project_id", $pid);
            projectStatusify($prj, "ON HOLD");
            projectUI($pid);
            last;
        } elsif ($instr =~ /^a/) {
            addNote("project_id", $pid);
            projectStatusify($prj, "ABANDONED");
            projectUI($pid);
            last;
        } else {
            print "Invalid command. Please try again.\n";
        }
    }
}

sub linkList {
    my ($prj, $tid) = @_;

    my $links = getLinksOfProject($prj->{project_id});

    print "\n==================\n";
    print "LINKS FOR PROJECT: $prj->{title}\n";
    print "==================\n";
    foreach my $link (@{$links}) {
        print "[$link->{link_id}]\t$link->{description}\n";
        print "\t\t$link->{path}\n";
    }

    while (1) {
        print "\n----------------------\n";
        my $instr = prompt("(b)ack, (a)dd, (q)uit:");

        if ($instr =~ /^b/) {
            if ($tid) {
                taskUI($tid);
            } else {
                projectUI($prj->{project_id});
            }
            last;
        } elsif ($instr =~ /^a/) {
            addLink($prj->{project_id});
            projectUI($prj->{project_id});
            last;
        } else {
            print "Invalid command. Please try again.\n";
        }
    }
}

sub projectNotesView {
    my ($prj, $notes) = @_;

    print "\n==================\n";
    print "NOTES FOR PROJECT: $prj->{title}\n";
    print "==================\n";
    foreach my $note (@{$notes}) {
        print "\n$note->{created}\n";
        print "-------------------\n";
        print "$note->{content}\n";
    }

    while (1) {
        print "\n----------------------\n";
        my $instr = prompt("(b)ack, (a)dd, (q)uit:");

        if ($instr =~ /^b/) {
            projectUI($prj->{project_id});
            last;
        } elsif ($instr =~ /^a/) {
            addNote("project_id", $prj->{project_id});
            projectUI($prj->{project_id});
            last;
        } else {
            print "Invalid command. Please try again.\n";
        }
    }
}

sub taskNotesView {
    my ($task, $notes) = @_;

    print "\n\n===============\n";
    print "NOTES FOR TASK: $task->{description}\n";
    print "===============\n";
    foreach my $note (@{$notes}) {
        print "\n$note->{created}\n";
        print "-------------------\n";
        print "$note->{content}\n";
    }

    while (1) {
        print "\n----------------------\n";
        my $instr = prompt("(b)ack, (a)dd, (q)uit:");

        if ($instr =~ /^b/) {
            taskUI($task->{task_id});
            last;
        } elsif ($instr =~ /^a/) {
            addNote("task_id", $task->{task_id});
            taskUI($task->{task_id});
            last;
        } else {
            print "Invalid command. Please try again.\n";
        }
    }
}

sub taskList {
    my ($prj) = @_;

    my $tasks = getTasksOfProject($prj->{project_id});

    print "\n==================\n";
    print "TASKS FOR PROJECT: $prj->{title}\n";
    print "==================\n";
    foreach my $task (@{$tasks}) {
        print "[$task->{task_id}]\t";
        if ($task->{next}) {
            print "NEXT";
        }
        print "\t$task->{status}\t$task->{description}\n";
    }

    while (1) {
        print "\n-------------------------------\n";
        my $instr = prompt("(#)task, (a)dd, (b)ack, (q)uit:");

        if ($instr =~ /^\d+/) {
            taskUI($instr);
            last;
        } elsif ($instr =~ /^a/) {
            addTask($prj->{project_id});
            taskList($prj);
            last;
        } elsif ($instr =~ /^b/) {
            projectUI($prj->{project_id});
            last;
        } else {
            print "Invalid command. Please try again.\n";
        }
    }
}

sub taskUI {
    my ($tid) = @_;

    my $task = getTask($tid);
    my $prj = getProject($task->{project_id});
    my $notes = getNotesOfTask($tid);

    my $num_notes = @{$notes};
    my $lastNoteTime = "";
    if ($num_notes) {
        $lastNoteTime = "\t\t[$notes->[-1]{created}]";
    }

    print "\n\n==============\n";
    print "TASK PAGE FOR: $task->{description}\n";
    print "Part of project '$prj->{title}'\n";
    print "==============\n";
    print "NEXT:   $task->{next}\n";
    print "STATUS: $task->{status}\t[$task->{updated}]\n";
    print "NOTES:  $num_notes$lastNoteTime\n";

    # START HERE WITH NEXT WORK

    while (1) {
        print "\n---------------------------------------------------------------------------\n";
        my $instr = prompt("(n)otes n(e)xt (l)inks ti(m)eline (s)tart (c)ompl. (h)old (b)ack (q)uit:");

        if ($instr =~ /^n/) {
            taskNotesView($task, $notes);
            last;
        } elsif ($instr =~ /^e/) {
            nextify($tid, $prj);
            taskUI($tid);
            last;
        } elsif ($instr =~ /^l/) {
            linkList($prj, $tid);
            last;
        } elsif ($instr =~ /^m/) {
            taskTimeline($task);
            taskUI($tid);
            last;
        } elsif ($instr =~ /^s/) {
            addNote("task_id", $tid);
            taskStatusify($task, "IN PROGRESS", $prj);
            taskUI($tid);
            last;
        } elsif ($instr =~ /^c/) {
            addNote("task_id", $tid);
            taskStatusify($task, "COMPLETE", $prj);
            taskUI($tid);
            last;
        } elsif ($instr =~ /^h/) {
            addNote("task_id", $tid);
            taskStatusify($task, "ON HOLD", $prj);
            taskUI($tid);
            last;
        } elsif ($instr =~ /^a/) {
            addNote("task_id", $tid);
            taskStatusify($task, "ABANDONED", $prj);
            taskUI($tid);
            last;
        } elsif ($instr =~ /^b/) {
            projectUI($prj->{project_id});
            last;
        } else {
            print "Invalid command. Please try again.\n";
        }
    }
}

sub projectTimeline {
    my ($prj) = @_;

    my $tasks = getTasksOfProject($prj->{project_id});
    my $notes = getNotesOfProject($prj->{project_id});
    my $status_updates = getUpdatesOfProject($prj->{project_id});

    my ($fh, $filename) = tempfile();

    print $fh "\n\n====================\n";
    print $fh "TIMELINE FOR PROJECT $prj->{title}\n";
    print $fh "====================\n";

    my @events;

    foreach my $note (@{$notes}) {
        push(@events, $note);
    }
    foreach my $update (@{$status_updates}) {
        push(@events, $update);
    }
    foreach my $task (@{$tasks}) {

        my $creation = {
            newtask => $task->{description},
            created => $task->{created}
        };
        push(@events, $creation);

        my $updates = getUpdatesOfTask($task->{task_id});
        foreach my $update (@{$updates}) {
            my $task_update = {
                description => $task->{description},
                status => $update->{status},
                created => $update->{created}
            };
            push(@events, $task_update);
        }
    }

    my @sorted = sort {$$a{"created"} cmp $$b{"created"}} @events;

    foreach my $event (@sorted) {
        print $fh "\n$event->{created}\n";
        print $fh "-------------------\n";
        if ($event->{content}) {
            print $fh "$event->{content}";
        } elsif ($event->{newtask}) {
            print $fh "TASK '$event->{newtask}' ADDED.\n";
        } elsif ($event->{description}) {
            print $fh "TASK '$event->{description}' STATUS updated to $event->{status}.\n";
        } else {
            print $fh "PROJECT STATUS updated to $event->{status}.\n";
        }
    }

    close($fh);

    system("less < $filename");

}

sub taskTimeline {
    my ($task) = @_;

    my $notes = getNotesOfTask($task->{task_id});
    my $status_updates = getUpdatesOfTask($task->{task_id});

    my ($fh, $filename) = tempfile();

    print $fh "\n\n==================\n";
    print $fh "TIMELINE FOR TASK: $task->{description}\n";
    print $fh "==================\n";

    my @events;

    foreach my $note (@{$notes}) {
        push(@events, $note);
    }
    foreach my $update (@{$status_updates}) {
        push(@events, $update);
    }

    my @sorted = sort {$$a{"created"} cmp $$b{"created"}} @events;

    foreach my $event (@sorted) {
        print $fh "\n$event->{created}\n";
        print $fh "-------------------\n";
        if ($event->{content}) {
            print $fh "$event->{content}";
        } else {
            print $fh "STATUS updated to $event->{status}.\n";
        }
    }

    close($fh);

    system("less < $filename");

}

sub addLink {
    # $tid is optional
    my ($pid, $tid) = @_;
    
}

# TODO -> What to do with open tasks when a project is closed?
sub projectStatusify {
    my ($prj, $status) = @_;

    my $stht = $dbh->prepare("UPDATE projects SET status = ?, updated = CURRENT_TIMESTAMP WHERE project_id = ?");
    execWithCheck($stht->execute($status, $prj->{project_id}));
    my $sthu = $dbh->prepare("INSERT INTO status_updates (project_id, status) VALUES (?, ?)");
    execWithCheck($sthu->execute($prj->{project_id}, $status));
    print "Project '$prj->{title}' set to '$status'.\n";
}

# TODO -- make these transactions
sub taskStatusify {
    my ($task, $status, $prj) = @_;

    my $stht = $dbh->prepare("UPDATE tasks SET status = ?, updated = CURRENT_TIMESTAMP WHERE task_id = ?");
    execWithCheck($stht->execute($status, $task->{task_id}));
    my $sths = $dbh->prepare("INSERT INTO status_updates (task_id, status) VALUES (?, ?)");
    execWithCheck($sths->execute($task->{task_id}, $status));
    if ($task->{next} == 1 and $status eq "COMPLETE") {
        my $sth = $dbh->prepare("UPDATE tasks SET next = 0 WHERE task_id = ?");
        execWithCheck($sth->execute($task->{task_id}));
    }
    print "Task '$task->{description}' set to '$status'.\n";

    # if setting the task to IN PROGRESS, do the same for the project
    if (($prj->{status} eq "NOT STARTED" or $prj->{status} eq "ON HOLD") and $status eq "IN PROGRESS") {
        my $sthp = $dbh->prepare("UPDATE projects SET status = ? WHERE project_id = ?");
        execWithCheck($sthp->execute($status, $prj->{project_id}));
        my $sthu = $dbh->prepare("INSERT INTO status_updates (project_id, status) VALUES (?, ?)");
        execWithCheck($sthu->execute($prj->{project_id}, $status));
        print "Projct '$prj->{title}' set to '$status'.\n";
    }
}

sub nextify {
    my ($tid, $prj) = @_;
    my $tasks_of_project = getTasksOfProject($prj->{project_id});
    foreach my $task (@{$tasks_of_project}) {
        if ($task->{next} == 1) {
            my $sth = $dbh->prepare("UPDATE tasks SET next = 0 WHERE task_id = ?");
            execWithCheck($sth->execute($task->{task_id}));
        }
    }
    my $sth = $dbh->prepare("UPDATE tasks SET next = 1 WHERE task_id = ?");
    execWithCheck($sth->execute($tid));
    my $task = getTask($tid);
    print "Task '$task->{description}' is now NEXT in project '$prj->{title}'.\n";
}

sub getProject {
    my ($pid) = @_;
    my $sth = $dbh->prepare("SELECT * FROM projects WHERE project_id = ?");
    execWithCheck($sth->execute($pid));
    my $prj = $sth->fetchrow_hashref;
    return $prj;
}

sub getTask {
    my ($tid) = @_;
    my $sth = $dbh->prepare("SELECT * FROM tasks WHERE task_id = ?");
    execWithCheck($sth->execute($tid));
    my $task = $sth->fetchrow_hashref;
    return $task;
}

sub getTasksOfProject {
    my ($pid) = @_;
    my $sth = $dbh->prepare("SELECT * FROM tasks WHERE project_id = ?");
    execWithCheck($sth->execute($pid));
    my @tasks;
    while (my $row = $sth->fetchrow_hashref) {
        push(@tasks, $row);
    }
    return \@tasks;
}

sub getNotesOfTask {
    my ($tid) = @_;
    my $sth = $dbh->prepare("SELECT * FROM notes WHERE task_id = ?");
    execWithCheck($sth->execute($tid));
    my @notes;
    while (my $row = $sth->fetchrow_hashref) {
        push(@notes, $row);
    }
    return \@notes;
}

sub getNotesOfProject {
    my ($pid) = @_;
    my $sth = $dbh->prepare("SELECT * FROM notes WHERE project_id = ?");
    execWithCheck($sth->execute($pid));
    my @notes;
    while (my $row = $sth->fetchrow_hashref) {
        push(@notes, $row);
    }
    return \@notes;
}

sub getUpdatesOfTask {
    my ($tid) = @_;
    my $sth = $dbh->prepare("SELECT * FROM status_updates WHERE task_id = ?");
    execWithCheck($sth->execute($tid));
    my @updates;
    while (my $row = $sth->fetchrow_hashref) {
        push(@updates, $row);
    }
    return \@updates;
}

sub getUpdatesOfProject {
    my ($pid) = @_;
    my $sth = $dbh->prepare("SELECT * FROM status_updates WHERE project_id = ?");
    execWithCheck($sth->execute($pid));
    my @updates;
    while (my $row = $sth->fetchrow_hashref) {
        push(@updates, $row);
    }
    return \@updates;
}

sub getLinksOfProject {
    my ($pid) = @_;

    my $sth = $dbh->prepare("SELECT * FROM links WHERE project_id = ?");
    execWithCheck($sth->execute($pid));
    my @links;
    while (my $row = $sth->fetchrow_hashref) {
        push(@links, $row);
    }
    return \@links;
}

sub addLink {
    my ($pid) = @_;
    #my ($pid, $tid) = @_;

    my $description = prompt("Link description:");
    my $path = prompt("File URL or Path:");

    my $sth = $dbh->prepare("INSERT INTO links (project_id, description, path) VALUES (?, ?, ?)");
    execWithCheck($sth->execute($pid, $description, $path));
    print "Link added.\n";

}

sub addProject {
    my $title = prompt("Project title:");
    my $priority = promptPri();
    my $sth = $dbh->prepare("INSERT INTO projects (title, priority, status) VALUES (?, ?, 'NOT STARTED')");
    execWithCheck($sth->execute($title, $priority));
    print "Project added.\n";
}

sub addTask {
    my ($pid) = @_;
    my $description = prompt("Task description:");
    my $sth = $dbh->prepare("INSERT INTO tasks (project_id, description, status) VALUES (?, ?, 'NOT STARTED')");
    execWithCheck($sth->execute($pid, $description));
    print "Task added.\n";
}

sub addNote {
    my ($id_field, $id) = @_;
    my $sth;
    if ($id_field eq "project_id") {
        $sth = $dbh->prepare("INSERT INTO notes (project_id, content) VALUES (?, ?);");
    } elsif ($id_field eq "task_id") {
        $sth = $dbh->prepare("INSERT INTO notes (task_id, content) VALUES (?, ?);");
    } else {
        die "Error in argument passed to addNote.";
    }
    my $content = getNote();
    execWithCheck($sth->execute($id, $content));
    print "Note added.\n";
}

sub getNote {
    my ($fh, $filename) = tempfile();
    my @lines;
    while(-z $filename) { # the -z check is here to make sure you don't save an empty file
        system("nvim", $filename);
    }
    while (my $line = <$fh>) {
        push(@lines, $line);
    }
    close($fh);
    return join("", @lines);
}

sub execWithCheck {
    my ($rv) = @_;
    if( $rv < 0 ) {
        print $DBI::errstr;
        $dbh->disconnect;
        exit 1;
    }
}

sub quitCheck {
    my ($input) = @_;
    if ($input eq "q" or $input eq "quit") {
        print "Quitting...\n\n";
        $dbh->disconnect;
        exit 1;
    } else {
        return $input;
    }
}

sub prompt {
    my ($prompt) = @_;
    print "$prompt\n>> ";
    my $response = <STDIN>;
    chomp($response);
    return quitCheck($response);
}

sub promptPri {
    my $response;
    while (1) {
        my $response = prompt("Priority [0-5]:");
        if ($response =~ /^[0-5]$/) {
            return $response;
        }
        print "Invalid input. Please enter an integer, 0 through 5.\n";
    }
}
