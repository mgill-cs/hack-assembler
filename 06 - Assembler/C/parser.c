#include <stdio.h>
#include "parser.h"

char** parse_instruction(const char* file, int* count) {
    char **instruction = malloc(MAX_LINES * sizeof(char*));
    FILE *fp = fopen(file, "r");
    char line[256];
    while (fgets(line, sizeof(line), fp)){
        char *comment = strstr(line, "//");
        if (comment != NULL) {
            *comment = '\0';
        }
        char *start = line;
        while (*start == ' ' || *start == '\t') start++;
        char *end = start + strlen(start) - 1;
        while (end > start && (*end == ' ' || *end == '\t' || *end == '\n'))
            end--;
        *(end + 1) = '\0';
    }
    fclose(fp);
    return instruction;
}




function parse_instruction($file) {
    $instruction = [];

    $handle = fopen($file, "r");
    while (($line = fgets($handle)) !== false) {
        // Comments start with "//" — keep only the code before it.
        $i1 = trim(explode("//", $line)[0]);

        // Skip lines that are blank or were comment-only.
        if ($i1 !== "") {
            $instruction[] = $i1;
        }
    }
    fclose($handle);

    return $instruction;
}