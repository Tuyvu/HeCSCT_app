from fastapi import FastAPI, Request
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import math
import re
import networkx as nx
from collections import defaultdict

app = FastAPI(title="Inference API", version="1.0")

# -----------------------------
# 🧩 Cấu trúc dữ liệu đầu vào
# -----------------------------
class Rule(BaseModel):
    id: int
    input: str
    output: str
    formula: str
    converted_formula: Optional[str] = None

class InferenceRequest(BaseModel):
    rules: List[Rule]
    event: str      # ví dụ: "a=3,b=4,C=60" hoặc "a,b,C" cho symbolic
    conclusion: str # ví dụ: "c"
    type: str = "forward"  # "forward" hoặc "backward"
    graph_type: str = "fpg"  # "fpg" hoặc "rpg"

# -----------------------------
# Hàm hỗ trợ
# -----------------------------
def convert_formula(formula: str) -> str:
    if '=' in formula:
        lhs, rhs = formula.split('=', 1)
        rhs = rhs.strip()
    else:
        rhs = formula
    rhs = rhs.replace('²', '**2')
    rhs = rhs.replace('·', '*')
    rhs = rhs.replace('√', 'math.sqrt')
    def replace_trig(m):
        func = m.group(1)
        arg = m.group(2)
        if func in ['acos', 'asin', 'atan']:
            return f'math.degrees(math.{func}({arg}))'
        else:
            return f'math.{func}(math.radians({arg}))'
    rhs = re.sub(r'(cos|sin|tan|acos|asin|atan)([A-Z])', replace_trig, rhs)
    return rhs

def parse_event(event_str: str) -> Dict[str, float]:
    """Chuyển chuỗi a=3,b=4,C=60 -> dict {'a':3,'b':4,'C':60}, hoặc a,b,C -> {'a':None, ...}"""
    values = {}
    for part in event_str.split(","):
        part = part.strip()
        if "=" in part:
            k, v = part.split("=")
            k = k.strip()
            try:
                v = float(v.strip())
            except:
                v = None
        else:
            k = part
            v = None
        if k:
            values[k] = v
    return values

def build_graph(rules: List[Rule], graph_type: str) -> Dict[str, List[Dict]]:
    """
    Build FPG or RPG in Cytoscape-compatible format.
    - FPG: Nodes are facts, edges from premises to conclusion (f -> f').
    - RPG: Nodes are rules, edges from ri to rj if ri's output is in rj's input.
    """
    G = nx.DiGraph()
    nodes = []
    edges = []
    node_ids = set()

    if graph_type == 'fpg':
        # FPG: Facts as nodes, edges from premises to conclusion
        for rule in rules:
            premises = [p.strip() for p in rule.input.split(",") if p.strip()]
            output = rule.output.strip()
            # Add fact nodes
            for p in premises:
                if p not in node_ids:
                    nodes.append({"data": {"id": p, "label": p}})
                    node_ids.add(p)
            if output not in node_ids:
                nodes.append({"data": {"id": output, "label": output}})
                node_ids.add(output)
            # Add edges: premise -> conclusion
            for p in premises:
                edges.append({"data": {"source": p, "target": output}})
    
    elif graph_type == 'rpg':
        # RPG: Rules as nodes, edges ri -> rj if ri's output is in rj's input
        rule_outputs = {rule.id: rule.output.strip() for rule in rules}
        for rule in rules:
            rule_node = f"R{rule.id}"
            if rule_node not in node_ids:
                nodes.append({"data": {"id": rule_node, "label": f"R{rule.id} ({rule.input} → {rule.output})"}})
                node_ids.add(rule_node)
        
        # Add edges: ri -> rj if ri's output is in rj's input
        for ri in rules:
            ri_output = ri.output.strip()
            for rj in rules:
                if ri.id != rj.id:
                    rj_premises = [p.strip() for p in rj.input.split(",") if p.strip()]
                    if ri_output in rj_premises:
                        edges.append({"data": {"source": f"R{ri.id}", "target": f"R{rj.id}"}})
    
    else:
        raise ValueError("Invalid graph_type: must be 'fpg' or 'rpg'")

    return {"nodes": nodes, "edges": edges}

def get_shortest_path_rules(rules: List[Rule], known: set, goal: str, graph_type: str) -> List[Rule]:
    """
    Select rules based on shortest path to goal in FPG (for forward) or RPG (for backward).
    """
    G = nx.DiGraph()
    
    if graph_type == 'fpg':
        # Build FPG for shortest path
        for rule in rules:
            premises = [p.strip() for p in rule.input.split(",") if p.strip()]
            output = rule.output.strip()
            for p in premises:
                G.add_edge(p, output, rule_id=rule.id)
    
        # Find rules on shortest paths from known facts to goal
        applicable_rules = []
        for fact in known:
            try:
                paths = nx.all_shortest_paths(G, source=fact, target=goal)
                for path in paths:
                    for i in range(len(path) - 1):
                        rule_id = G[path[i]][path[i+1]].get('rule_id')
                        if rule_id is not None:
                            rule = next(r for r in rules if r.id == rule_id)
                            if rule not in applicable_rules:
                                applicable_rules.append(rule)
            except nx.NetworkXNoPath:
                continue
        return applicable_rules if applicable_rules else rules  # Fallback to all rules if no path
    
    elif graph_type == 'rpg':
        # Build RPG for shortest path
        rule_outputs = {rule.id: rule.output.strip() for rule in rules}
        for ri in rules:
            ri_output = ri.output.strip()
            for rj in rules:
                if ri.id != rj.id:
                    rj_premises = [p.strip() for p in rj.input.split(",") if p.strip()]
                    if ri_output in rj_premises:
                        G.add_edge(f"R{ri.id}", f"R{rj.id}")
        
        # Find rules leading to goal-producing rules
        goal_rules = [r for r in rules if r.output.strip() == goal]
        applicable_rules = []
        for gr in goal_rules:
            try:
                for rule in rules:
                    if nx.has_path(G, f"R{rule.id}", f"R{gr.id}"):
                        if rule not in applicable_rules:
                            applicable_rules.append(rule)
                if gr not in applicable_rules:
                    applicable_rules.append(gr)
            except nx.NodeNotFound:
                continue
        return applicable_rules if applicable_rules else rules  # Fallback to all rules if no path
    
    else:
        return rules  # Fallback to all rules if graph_type is invalid

# -----------------------------
# Hàm xử lý suy diễn
# -----------------------------
def forward_chain(rules: List[Rule], known: set, goal: str, values: Dict[str, float]):
    derived = set(known)
    steps = []
    used_rules = set()  # Track used rule IDs
    
    # Select rules based on shortest path in FPG
    applicable_rules = get_shortest_path_rules(rules, known, goal, 'fpg')
    
    while True:
        new_fact = None
        for rule in applicable_rules:
            premises = [p.strip() for p in rule.input.split(",") if p.strip()]
            conclusion = rule.output.strip()
            expr = rule.converted_formula.strip()
            if all(p in derived for p in premises) and conclusion not in derived:
                derived.add(conclusion)
                new_fact = conclusion
                used_rules.add(rule.id)
                step = {
                    "rule_id": rule.id,
                    "premises": premises,
                    "conclusion": conclusion,
                    "formula": rule.formula,
                    "converted_formula": expr,
                    "result": None
                }
                if all(values.get(p) is not None for p in premises):
                    try:
                        result = eval(expr, {
                            "__builtins__": None,
                            "math": math,
                            "sqrt": math.sqrt,
                            "cos": math.cos,
                            "radians": math.radians,
                            "acos": math.acos,
                            "asin": math.asin,
                            "degrees": math.degrees
                        }, values)
                        values[conclusion] = result
                        step["result"] = result
                    except Exception as e:
                        step["result"] = f"Lỗi: {e}"
                else:
                    step["result"] = "Derived symbolically"
                steps.append(step)
                if conclusion == goal:
                    return {
                        "success": True,
                        "conclusion": f"{goal} = {values.get(goal, 'Derived symbolically')}",
                        "trace": steps,
                        "used_rules": [f"R{rule_id}" for rule_id in sorted(used_rules)]
                    }
        if not new_fact:
            break
    
    success = goal in derived
    conclusion_msg = f"{goal} = {values.get(goal, 'Derived symbolically' if success else 'Không tìm thấy')}"
    return {
        "success": success,
        "conclusion": conclusion_msg,
        "trace": steps,
        "used_rules": [f"R{rule_id}" for rule_id in sorted(used_rules)]
    }

def backward_chain(rules: List[Rule], known: set, goal: str, values: Dict[str, float], visited: set = None, steps: List = None):
    if visited is None:
        visited = set()
    if steps is None:
        steps = []
    used_rules = set() if not steps else {s["rule_id"] for s in steps}  # Track used rule IDs
    
    if goal in known:
        return True, steps, used_rules
    if goal in visited:
        return False, steps, used_rules
    visited.add(goal)
    
    # Select rules based on shortest path in RPG
    applicable_rules = get_shortest_path_rules(rules, known, goal, 'rpg')
    
    for rule in applicable_rules:
        if rule.output.strip() == goal:
            premises = [p.strip() for p in rule.input.split(",") if p.strip()]
            all_known = True
            sub_steps = []
            sub_used_rules = set()
            for p in premises:
                if p not in known:
                    success, new_steps, new_used_rules = backward_chain(rules, known, p, values, visited, sub_steps)
                    sub_steps = new_steps
                    sub_used_rules.update(new_used_rules)
                    if not success:
                        all_known = False
                        break
            if all_known:
                step = {
                    "rule_id": rule.id,
                    "premises": premises,
                    "conclusion": goal,
                    "formula": rule.formula,
                    "converted_formula": rule.converted_formula,
                    "result": None
                }
                if all(values.get(p) is not None for p in premises):
                    try:
                        result = eval(rule.converted_formula, {
                            "__builtins__": None,
                            "math": math,
                            "sqrt": math.sqrt,
                            "cos": math.cos,
                            "radians": math.radians,
                            "acos": math.acos,
                            "asin": math.asin,
                            "degrees": math.degrees
                        }, values)
                        values[goal] = result
                        step["result"] = result
                    except Exception as e:
                        step["result"] = f"Lỗi: {e}"
                else:
                    step["result"] = "Derived symbolically"
                steps.extend(sub_steps)
                steps.append(step)
                used_rules.add(rule.id)
                used_rules.update(sub_used_rules)
                known.add(goal)
                return True, steps, used_rules
    return False, steps, used_rules

# -----------------------------
# ⚡ API Endpoint
# -----------------------------
@app.post("/infer")
async def infer(request: InferenceRequest):
    # Convert formulas if not provided
    for rule in request.rules:
        if rule.converted_formula is None:
            rule.converted_formula = convert_formula(rule.formula)
    
    event = request.event
    goal = request.conclusion
    inf_type = request.type
    graph_type = request.graph_type

    values = parse_event(event)
    known = set(values.keys())

    if inf_type == "forward":
        result = forward_chain(request.rules, known, goal, values)
    elif inf_type == "backward":
        success, trace, used_rules = backward_chain(request.rules, known, goal, values)
        conclusion_msg = f"{goal} = {values.get(goal, 'Derived symbolically' if success else 'Không tìm thấy')}"
        result = {
            "success": success,
            "conclusion": conclusion_msg,
            "trace": trace,
            "used_rules": [f"R{rule_id}" for rule_id in sorted(used_rules)]
        }
    else:
        return {"error": "Invalid type: must be 'forward' or 'backward'"}

    # Build and add Cytoscape-compatible graph
    result["graph"] = build_graph(request.rules, graph_type)

    return result