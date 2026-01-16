#!/usr/bin/env python3
"""
ESESP Yearly Progress Diagram Generator
Generates accumulated progress charts by month with goal line
"""

import sys
import json
import matplotlib.pyplot as plt
import matplotlib.patches as patches
import numpy as np
from datetime import datetime

def generate_yearly_diagram(data_file, output_file):
    """
    Generate yearly progress diagram showing:
    - Accumulated bar chart by month (cyan)
    - Goal line (dark blue)
    - Percentages on each bar
    - Year-over-year comparison
    """
    
    # Load data
    with open(data_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Debug: Print received data
    print(f"DEBUG: Received data keys: {data.keys()}")
    print(f"DEBUG: Monthly scores type: {type(data.get('monthly_scores'))}")
    print(f"DEBUG: Sample monthly_scores: {list(data.get('monthly_scores', {}).items())[:3]}")
    
    # Extract values
    year = data.get('year', datetime.now().year)
    category = data.get('category', 'Todas as Categorias')
    monthly_data = data.get('monthly_scores', {})  # {month: score}
    monthly_counts = data.get('monthly_counts', {})  # {month: count}
    goal_scores = data.get('goal_scores', {})  # {month: goal}
    
    # ESESP colors
    esesp_blue = '#1e3a5f'
    accumulated_color = '#0891b2'  # Cyan for accumulated
    goal_line_color = '#1e3a5f'  # Dark blue for goal line
    
    # Prepare data
    months = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 
              'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ']
    
    # Get scores for each month
    accumulated_scores = []
    goal_values = []
    
    for i in range(1, 13):
        # Convert int to string to match JSON keys
        month_key = str(i)
        accumulated_scores.append(monthly_data.get(month_key, 0))
        goal_values.append(goal_scores.get(month_key, 0))
    
    # Debug: Print processed data
    print(f"DEBUG: Accumulated scores: {accumulated_scores}")
    print(f"DEBUG: Goal values: {goal_values}")
    print(f"DEBUG: Non-zero scores: {[s for s in accumulated_scores if s > 0]}")
    
    # Create figure
    fig, ax = plt.subplots(figsize=(14, 7))
    fig.patch.set_facecolor('white')
    
    # X positions
    x_pos = np.arange(len(months))
    
    # Plot accumulated bars - FIRST so goal line appears on top
    bars = ax.bar(x_pos, accumulated_scores, width=0.8, 
                   color=accumulated_color, alpha=0.85, 
                   label='Executado (Acumulado)', edgecolor=esesp_blue, linewidth=1.5, zorder=5)
    
    # Add percentage labels on bars
    for i, (bar, score, count) in enumerate(zip(bars, accumulated_scores, 
                                                 [monthly_counts.get(j+1, 0) for j in range(12)])):
        if score > 0:
            height = bar.get_height()
            # Main percentage
            ax.text(bar.get_x() + bar.get_width()/2., height + 1,
                    f'{score:.0f}%',
                    ha='center', va='bottom', fontsize=10, fontweight='bold',
                    color=esesp_blue)
            
            # Response count inside bar if space
            if height > 8 and count > 0:
                ax.text(bar.get_x() + bar.get_width()/2., height/2,
                        f'{count}',
                        ha='center', va='center', fontsize=8,
                        color='white', fontweight='bold')
    
    # Plot goal line
    ax.plot(x_pos, goal_values, color=goal_line_color, linewidth=3, 
            label='Previsto', marker='o', markersize=6, markerfacecolor='white',
            markeredgewidth=2, markeredgecolor=goal_line_color, zorder=10)
    
    # Add goal values as text
    for i, (x, y) in enumerate(zip(x_pos, goal_values)):
        if y > 0:
            ax.text(x, y + 3, f'{y:.1f}%',
                    ha='center', va='bottom', fontsize=9,
                    color=goal_line_color, fontweight='bold')
    
    # Configure axes
    ax.set_ylabel('Pontuação (%)', fontsize=13, fontweight='bold', color=esesp_blue)
    ax.set_xlabel('Meses', fontsize=13, fontweight='bold', color=esesp_blue)
    ax.set_xticks(x_pos)
    ax.set_xticklabels(months, fontsize=11, fontweight='bold')
    ax.set_ylim(0, 110)
    ax.set_yticks(range(0, 101, 10))
    
    # Grid
    ax.grid(axis='y', alpha=0.3, linestyle='-', linewidth=0.5)
    ax.set_axisbelow(True)
    
    # Title
    if category == 'Todas as Categorias':
        title = f'Evolução Anual - {year}'
    else:
        title = f'{category} - Evolução {year}'
    
    ax.text(0.5, 1.05, title, transform=ax.transAxes,
            fontsize=16, fontweight='bold', ha='center', color=esesp_blue)
    
    # Legend
    ax.legend(loc='upper left', fontsize=11, framealpha=0.9)
    
    # Statistics box
    total_responses = sum(monthly_counts.values())
    avg_score = np.mean([s for s in accumulated_scores if s > 0]) if any(accumulated_scores) else 0
    
    stats_text = f'Total de Respostas: {total_responses}\nMédia Anual: {avg_score:.1f}%'
    ax.text(0.98, 0.97, stats_text, transform=ax.transAxes,
            fontsize=10, ha='right', va='top',
            bbox=dict(boxstyle='round,pad=0.7', facecolor='#f0f9ff',
                     edgecolor=esesp_blue, linewidth=2),
            color=esesp_blue, fontweight='bold')
    
    # ESESP branding
    ax.text(0.02, 0.02, 'ESESP - Observatório', transform=ax.transAxes,
            fontsize=9, ha='left', va='bottom', color=esesp_blue, fontweight='bold')
    
    # Remove top and right spines
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    ax.spines['left'].set_color(esesp_blue)
    ax.spines['bottom'].set_color(esesp_blue)
    ax.spines['left'].set_linewidth(1.5)
    ax.spines['bottom'].set_linewidth(1.5)
    
    # Adjust layout
    plt.tight_layout()
    plt.subplots_adjust(top=0.92, bottom=0.10)
    
    # Save
    plt.savefig(output_file, dpi=300, bbox_inches='tight',
                facecolor='white', edgecolor='none')
    plt.close()
    
    return True

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Usage: python3 generate_yearly_diagram.py <input_json> <output_png>")
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    try:
        generate_yearly_diagram(input_file, output_file)
        print(f"Yearly diagram generated: {output_file}")
        sys.exit(0)
    except Exception as e:
        print(f"Error: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)