# Reads and parses the file
def parse_instruction(file):
    with open(file, "r") as f:
        instruction = []
        for line in f:
            i1 = line.split("//")[0].strip()
            if i1:
                instruction.append(i1.strip())
    return instruction
